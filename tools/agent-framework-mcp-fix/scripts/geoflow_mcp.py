"""Expose GEOFlow API v1 through MCP stdio; no PHP or Docker access required."""

import os
import re
import shutil
import subprocess
import sys
import uuid
from pathlib import Path
from typing import Annotated, Any, Literal
from urllib.parse import urlsplit

import httpx
from dotenv import load_dotenv
from mcp.server.mcpserver import MCPServer
from mcp.types import ToolAnnotations
from pydantic import Field

PositiveId = Annotated[int, Field(ge=1)]
Page = Annotated[int, Field(ge=1)]
PageSize = Annotated[int, Field(ge=1, le=100)]
MaterialType = Literal[
    "categories", "authors", "keyword-libraries", "title-libraries",
    "image-libraries", "knowledge-bases",
]
ItemType = Literal[
    "keyword-libraries", "title-libraries", "image-libraries", "knowledge-bases",
]


def create_server(environment):
    base = environment.get("AGENT_GEOFLOW_BASE_URL", "").strip().rstrip("/")
    token = environment.get("AGENT_GEOFLOW_API_TOKEN", "").strip()
    parts = urlsplit(base)
    if (
        parts.scheme not in ("http", "https") or not parts.hostname
        or parts.username or parts.password or parts.query or parts.fragment
    ):
        raise ValueError("请配置 AGENT_GEOFLOW_BASE_URL 为 GEOFlow Web 根地址")
    if not token:
        raise ValueError("请配置 AGENT_GEOFLOW_API_TOKEN")
    if base.endswith("/api/v1"):
        raise ValueError("AGENT_GEOFLOW_BASE_URL 不应包含 /api/v1")
    timeout = int(environment.get("AGENT_GEOFLOW_TIMEOUT", "30"))
    if not 5 <= timeout <= 180:
        raise ValueError("AGENT_GEOFLOW_TIMEOUT 必须在 5 到 180 秒之间")
    tls = environment.get("AGENT_GEOFLOW_VERIFY_TLS", "true").strip().lower()
    if tls not in ("true", "false"):
        raise ValueError("AGENT_GEOFLOW_VERIFY_TLS 必须为 true 或 false")
    server = MCPServer("geoflow")

    async def read(path, params=None):
        result = {
            "success": False, "operation": "read", "resource": path,
            "state": "failed", "verification": {"api_response_received": False},
        }
        try:
            async with httpx.AsyncClient(
                timeout=timeout, verify=tls == "true", follow_redirects=False,
            ) as client:
                response = await client.get(
                    f"{base}/api/v1/{path}", params=params,
                    headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
                )
            result["verification"] = {
                "api_response_received": True, "http_status": response.status_code,
            }
            if not response.is_success:
                messages = {
                    401: "Token 无效或已过期，请检查 AGENT_GEOFLOW_API_TOKEN",
                    403: "Token 缺少该业务接口的读取 scope",
                    404: "GEOFlow API 路由或资源不存在，请检查部署版本和基础地址",
                }
                result["error"] = messages.get(
                    response.status_code, f"GEOFlow 返回 HTTP {response.status_code}",
                )
                return result
            payload = response.json()
            if not isinstance(payload, dict):
                raise ValueError("Invalid API response")
            if payload.get("success") is False or payload.get("error"):
                result["error"] = "GEOFlow API 返回业务错误，请查看服务端日志"
                return result
            result.update(success=True, state="read", data=payload.get("data", payload))
            return result
        except httpx.HTTPError:
            result["error"] = "无法连接 GEOFlow，请检查服务地址、端口、TLS 和网络"
        except ValueError:
            result["error"] = "GEOFlow 未返回有效的 API JSON，请检查地址和反向代理"
        return result

    async def write(method, path, payload, idempotency_key=None):
        result = {
            "success": False, "operation": method.lower(), "resource": path,
            "state": "failed", "verification": {"api_response_received": False},
        }
        headers = {
            "Authorization": f"Bearer {token}", "Accept": "application/json",
            "Content-Type": "application/json",
        }
        if idempotency_key:
            headers["X-Idempotency-Key"] = idempotency_key
        try:
            async with httpx.AsyncClient(
                timeout=timeout, verify=tls == "true", follow_redirects=False,
            ) as client:
                response = await client.request(
                    method, f"{base}/api/v1/{path}", json=payload, headers=headers,
                )
            result["verification"] = {
                "api_response_received": True, "http_status": response.status_code,
            }
            if not response.is_success:
                result["error"] = {
                    401: "Token 无效或已过期，请检查 AGENT_GEOFLOW_API_TOKEN",
                    403: "Token 缺少该业务接口的写入 scope",
                    404: "GEOFlow API 路由或资源不存在，请检查部署版本和基础地址",
                    422: "请求字段未通过 GEOFlow 校验",
                }.get(response.status_code, f"GEOFlow 返回 HTTP {response.status_code}")
                return result
            body = response.json()
            if not isinstance(body, dict) or body.get("success") is False or body.get("error"):
                result["error"] = "GEOFlow API 返回业务错误，请查看服务端日志"
                return result
            data = body.get("data", body)
            if path == "social-publication/jobs" and isinstance(data, dict):
                result.update(success=True, state="queued", data=data)
            else:
                result.update(success=True, state="saved", data=data)
            return result
        except httpx.HTTPError:
            result["error"] = "无法连接 GEOFlow，请检查服务地址、端口、TLS 和网络"
        except ValueError:
            result["error"] = "GEOFlow 未返回有效的 API JSON，请检查地址和反向代理"
        return result

    readonly = ToolAnnotations(readOnlyHint=True, destructiveHint=False, openWorldHint=True)

    @server.tool(annotations=readonly)
    async def geoflow_catalog() -> dict[str, Any]:
        """Read GEOFlow catalogs (models, libraries, categories). Requires catalog:read."""
        return await read("catalog")

    @server.tool(annotations=readonly)
    async def geoflow_list_articles(
        search: str = "", status: str = "", page: Page = 1, per_page: PageSize = 20,
    ) -> dict[str, Any]:
        """Search GEOFlow articles. Requires articles:read."""
        return await read("articles", {
            "search": search, "status": status, "page": page, "per_page": per_page,
        })

    @server.tool(annotations=readonly)
    async def geoflow_get_article(article_id: PositiveId) -> dict[str, Any]:
        """Read a GEOFlow article by ID. Requires articles:read."""
        return await read(f"articles/{article_id}")

    @server.tool(annotations=readonly)
    async def geoflow_list_social_platforms() -> dict[str, Any]:
        """List social publishing platforms supported by GEOFlow. Requires social:read."""
        return await read("social-publication/platforms")

    @server.tool(annotations=readonly)
    async def geoflow_list_social_accounts(
        platform: Annotated[str | None, Field(min_length=1, max_length=40)] = None,
    ) -> dict[str, Any]:
        """List active social accounts and executor readiness, without credentials. Requires social:read."""
        return await read("social-publication/accounts", {"platform": platform} if platform else None)

    @server.tool(annotations=ToolAnnotations(readOnlyHint=False, destructiveHint=False, openWorldHint=True))
    async def geoflow_save_article_draft(
        title: Annotated[str, Field(min_length=1, max_length=255)],
        content: Annotated[str, Field(min_length=1)],
        excerpt: Annotated[str | None, Field(max_length=1000)] = None,
        category_id: PositiveId | None = None,
        author_id: PositiveId | None = None,
        task_id: PositiveId | None = None,
        keywords: str | None = None,
        meta_description: Annotated[str | None, Field(max_length=320)] = None,
        idempotency_key: Annotated[str | None, Field(min_length=1, max_length=128)] = None,
    ) -> dict[str, Any]:
        """Save generated content as a GEOFlow draft. Requires articles:write; never publishes."""
        payload = {"title": title, "content": content, "status": "draft", "review_status": "pending"}
        payload.update({key: value for key, value in {
            "excerpt": excerpt, "category_id": category_id, "author_id": author_id,
            "task_id": task_id, "keywords": keywords, "meta_description": meta_description,
        }.items() if value is not None})
        return await write("POST", "articles", payload, idempotency_key or str(uuid.uuid4()))

    @server.tool(annotations=ToolAnnotations(readOnlyHint=False, destructiveHint=False, openWorldHint=True))
    async def geoflow_update_article_draft(
        article_id: PositiveId,
        title: Annotated[str | None, Field(min_length=1, max_length=255)] = None,
        content: Annotated[str | None, Field(min_length=1)] = None,
        excerpt: Annotated[str | None, Field(max_length=1000)] = None,
        keywords: str | None = None,
        meta_description: Annotated[str | None, Field(max_length=320)] = None,
    ) -> dict[str, Any]:
        """Update a GEOFlow article draft. Requires articles:write; confirm the target article first."""
        payload = {key: value for key, value in {
            "title": title, "content": content, "excerpt": excerpt,
            "keywords": keywords, "meta_description": meta_description,
        }.items() if value is not None}
        if not payload:
            return {"success": False, "operation": "patch", "resource": f"articles/{article_id}",
                    "state": "failed", "verification": {}, "error": "至少提供一个需要更新的字段"}
        return await write("PATCH", f"articles/{article_id}", payload, str(uuid.uuid4()))

    @server.tool(annotations=ToolAnnotations(readOnlyHint=False, destructiveHint=False, openWorldHint=True))
    async def geoflow_enqueue_social_publication(
        account_id: PositiveId,
        title: Annotated[str, Field(min_length=1, max_length=255)],
        content: Annotated[str, Field(min_length=1, max_length=20000)],
        excerpt: Annotated[str | None, Field(max_length=2000)] = None,
        images: list[Annotated[str, Field(min_length=1, max_length=2048)]] | None = None,
        idempotency_key: Annotated[str | None, Field(min_length=1, max_length=128)] = None,
    ) -> dict[str, Any]:
        """Queue a social publish job for the explicitly selected account. Requires social:publish. This queues work and does not mean the platform has published it."""
        payload = {"account_id": account_id, "title": title, "content": content}
        if excerpt is not None:
            payload["excerpt"] = excerpt
        if images is not None:
            payload["images"] = images
        return await write("POST", "social-publication/jobs", payload, idempotency_key or str(uuid.uuid4()))

    @server.tool(annotations=readonly)
    async def geoflow_get_social_job(job_id: PositiveId) -> dict[str, Any]:
        """Read a social executor job status by ID. Requires social:read."""
        return await read(f"social-publication/jobs/{job_id}")

    @server.tool(annotations=readonly)
    async def geoflow_list_tasks(
        search: str = "", status: str = "", page: Page = 1, per_page: PageSize = 20,
    ) -> dict[str, Any]:
        """Read GEOFlow task progress. Requires tasks:read."""
        return await read("tasks", {
            "search": search, "status": status, "page": page, "per_page": per_page,
        })

    @server.tool(annotations=readonly)
    async def geoflow_get_task(task_id: PositiveId) -> dict[str, Any]:
        """Read a GEOFlow task by ID. Requires tasks:read."""
        return await read(f"tasks/{task_id}")

    @server.tool(annotations=readonly)
    async def geoflow_get_job(job_id: PositiveId) -> dict[str, Any]:
        """Read a GEOFlow job execution status. Requires jobs:read."""
        return await read(f"jobs/{job_id}")

    @server.tool(annotations=readonly)
    async def geoflow_list_materials(
        material_type: MaterialType, search: str = "", page: Page = 1, per_page: PageSize = 20,
    ) -> dict[str, Any]:
        """Read GEOFlow material libraries. Requires materials:read."""
        return await read(f"materials/{material_type}", {
            "search": search, "page": page, "per_page": per_page,
        })

    @server.tool(annotations=readonly)
    async def geoflow_get_material_items(
        material_type: ItemType, material_id: PositiveId, page: Page = 1,
        per_page: PageSize = 20,
    ) -> dict[str, Any]:
        """Read keywords, titles or knowledge chunks in a library. Requires materials:read."""
        return await read(f"materials/{material_type}/{material_id}/items", {
            "page": page, "per_page": per_page,
        })

    return server


def command(environment):
    artisan = ["artisan", "boost:mcp"]
    artisan_env = environment.get("GEOFLOW_ARTISAN_ENV", "")
    if artisan_env:
        if not re.fullmatch(r"[A-Za-z0-9_-]+", artisan_env):
            raise ValueError("GEOFLOW_ARTISAN_ENV 不是有效的环境名称")
        artisan.append(f"--env={artisan_env}")
    container = environment.get("GEOFLOW_DOCKER_CONTAINER", "")
    if container:
        if not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9_.-]*", container):
            raise ValueError("GEOFLOW_DOCKER_CONTAINER 不是有效的容器名称")
        docker = shutil.which("docker")
        if not docker:
            raise ValueError("找不到 Docker，请安装 Docker 或配置本机 GEOFLOW_PHP")
        return [docker, "exec", "-i", container, "php", *artisan], None

    root = Path(environment.get("GEOFLOW_ROOT", "")).expanduser()
    if not environment.get("GEOFLOW_ROOT") or not (root / "artisan").is_file():
        raise ValueError("GEOFLOW_ROOT 必须指向包含 artisan 的 GEOFlow 项目目录")
    if not (root / "vendor" / "autoload.php").is_file():
        raise ValueError("GEOFlow 缺少 Composer 依赖，请先安装项目依赖")
    php = shutil.which(environment.get("GEOFLOW_PHP", "php"))
    if not php:
        raise ValueError("找不到 PHP，请设置 GEOFLOW_PHP 绝对路径或 GEOFLOW_DOCKER_CONTAINER")
    return [php, *artisan], str(root)


def main():
    try:
        load_dotenv(Path(__file__).resolve().parent.parent / ".env")
        if os.environ.get("GEOFLOW_MCP_MODE", "api") == "boost":
            # Explicit opt-in compatibility for local development only.
            args, cwd = command(os.environ)
            return subprocess.call(args, cwd=cwd)
        create_server(os.environ).run(transport="stdio")
        return 0
    except (ValueError, OSError) as error:
        print(f"GEOFlow MCP 启动失败：{error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
