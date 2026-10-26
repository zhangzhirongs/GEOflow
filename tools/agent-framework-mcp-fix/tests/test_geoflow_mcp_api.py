import importlib.util
import json
import os
import sys
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

import pytest
from mcp import ClientSession
from mcp.client.stdio import StdioServerParameters, stdio_client

SCRIPT = Path(__file__).resolve().parents[1] / "scripts" / "geoflow_mcp.py"
if not SCRIPT.exists():
    SCRIPT = Path(__file__).resolve().parents[1] / "tools" / "agent-framework-mcp-fix" / SCRIPT.name


@pytest.fixture
def api():
    seen = []

    class Handler(BaseHTTPRequestHandler):
        def do_GET(self):
            seen.append((self.path, self.headers.get("Authorization")))
            self.send_response(403 if self.path.endswith("/9") else 200)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({
                "success": True, "data": {"id": 7},
                "error": None if not self.path.endswith("/9") else "secret-provider-error",
            }).encode())

        def do_POST(self):
            body = json.loads(self.rfile.read(int(self.headers.get("Content-Length", "0"))))
            seen.append((self.path, self.headers.get("Authorization"), body))
            self.send_response(201)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"success": True, "data": {"id": 21, **body}}).encode())

        def do_PATCH(self):
            body = json.loads(self.rfile.read(int(self.headers.get("Content-Length", "0"))))
            seen.append((self.path, self.headers.get("Authorization"), body))
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"success": True, "data": {"id": 7, **body}}).encode())

        def log_message(self, *args):
            pass

    server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()
    yield f"http://127.0.0.1:{server.server_port}", seen
    server.shutdown()
    server.server_close()
    thread.join()


async def test_real_stdio_handshake_api_auth_errors_and_validation(api):
    base, seen = api
    params = StdioServerParameters(command=sys.executable, args=[str(SCRIPT)], env={
        **os.environ, "AGENT_GEOFLOW_BASE_URL": base,
        "AGENT_GEOFLOW_API_TOKEN": "test-only-token", "GEOFLOW_MCP_MODE": "api",
    })
    async with stdio_client(params) as (reader, writer):
        async with ClientSession(reader, writer) as session:
            await session.initialize()
            tools = (await session.list_tools()).tools
            assert len(tools) == 14
            assert all(tool.annotations.read_only_hint for tool in tools if tool.name not in {
                "geoflow_save_article_draft", "geoflow_update_article_draft", "geoflow_enqueue_social_publication",
            })
            result = await session.call_tool("geoflow_catalog", {})
            assert not result.is_error
            assert result.structured_content["success"] is True
            assert seen[-1] == ("/api/v1/catalog", "Bearer test-only-token")
            platforms = await session.call_tool("geoflow_list_social_platforms", {})
            assert platforms.structured_content["success"] is True
            assert seen[-1][0] == "/api/v1/social-publication/platforms"
            accounts = await session.call_tool("geoflow_list_social_accounts", {"platform": "xiaohongshu"})
            assert accounts.structured_content["success"] is True
            assert seen[-1][0] == "/api/v1/social-publication/accounts?platform=xiaohongshu"
            await session.call_tool("geoflow_get_material_items", {
                "material_type": "knowledge-bases", "material_id": 7,
            })
            assert seen[-1][0] == "/api/v1/materials/knowledge-bases/7/items?page=1&per_page=20"
            saved = await session.call_tool("geoflow_save_article_draft", {
                "title": "系统宣传", "content": "介绍正文", "category_id": 3,
            })
            assert saved.structured_content["success"] is True
            assert saved.structured_content["data"]["id"] == 21
            assert seen[-1][0] == "/api/v1/articles"
            assert seen[-1][2]["status"] == "draft"
            assert seen[-1][2]["review_status"] == "pending"
            updated = await session.call_tool("geoflow_update_article_draft", {
                "article_id": 7, "title": "更新标题",
            })
            assert updated.structured_content["success"] is True
            assert seen[-1][0] == "/api/v1/articles/7"
            queued = await session.call_tool("geoflow_enqueue_social_publication", {
                "account_id": 8, "title": "系统介绍", "content": "介绍正文",
            })
            assert queued.structured_content["success"] is True
            assert queued.structured_content["state"] == "queued"
            assert seen[-1][0] == "/api/v1/social-publication/jobs"
            assert seen[-1][2]["account_id"] == 8
            denied = await session.call_tool("geoflow_get_article", {"article_id": 9})
            assert denied.structured_content["success"] is False
            assert denied.structured_content["verification"]["http_status"] == 403
            assert "secret-provider-error" not in str(denied)
            assert "test-only-token" not in str(denied)
            count = len(seen)
            invalid = await session.call_tool("geoflow_get_material_items", {
                "material_type": "../../auth", "material_id": 7,
            })
            assert invalid.is_error
            invalid = await session.call_tool("geoflow_get_article", {"article_id": -1})
            assert invalid.is_error
            assert len(seen) == count


def test_missing_token_fails_before_starting_server():
    spec = importlib.util.spec_from_file_location("bridge", SCRIPT)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    with pytest.raises(ValueError, match="AGENT_GEOFLOW_API_TOKEN"):
        module.create_server({"AGENT_GEOFLOW_BASE_URL": "http://127.0.0.1:18082"})
