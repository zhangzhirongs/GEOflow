<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# 命令映射

## 面选择

当 `bin/geoflow` 存在时，将 GEOFlow CLI 0.2.0 视为首选的 API v1 客户端。运行 `--version` 和 `--help`，然后使用列出的命令进行目录、任务、作业、素材和文章操作。CLI 将身份验证、端点验证、JSON 信封、响应限制、重定向阻止、机密信息脱敏和删除确认保留在一个实现中。

当 CLI 不存在时，使用本参考中的 API v1 回退方案。对于 CLI 和 API v1 都缺少的能力，使用经过身份验证的管理员 Web 路由。在操作前检查当前路由：

```bash
php artisan route:list --path=api/v1
php artisan route:list --except-vendor
```

绝不要根据管理页面名称臆造 CLI 命令或 API 路由。

## 预检

```bash
scripts/geoflow_preflight.sh "<workspace>" [config] [checks]
```

`checks` 以逗号分隔，并接受 `catalog`、`materials`、`tasks`、`articles` 和 `admin`：

```bash
scripts/geoflow_preflight.sh "/path/to/GEOFlow"
scripts/geoflow_preflight.sh "/path/to/GEOFlow" "/path/to/profile.json" catalog,materials,tasks,articles
GEOFLOW_ADMIN_PATH=/geo_admin scripts/geoflow_preflight.sh "/path/to/GEOFlow" "" admin
```

CLI 模式始终验证 `--version`、`--help` 和 `config show`。然后将检查映射到以下只读命令：

| 检查项 | CLI 命令 |
|---|---|
| `catalog` | `catalog` |
| `materials` | `material summary` |
| `tasks` | `task list --per-page 1` |
| `articles` | `article list --per-page 1` |
| `admin` | 对已配置管理员登录页面的 HTTP 读取 |

如果提供了 config 参数，辅助程序会将 `--config` 传递给每次 CLI 调用。API 回退需要 `GEOFLOW_BASE_URL` 和 `GEOFLOW_API_TOKEN` 才能进行经过身份验证的检查。基础 URL 是公共 Web 根路径，例如 `http://127.0.0.1:18080`，不包含 `/api/v1` 或 admin 前缀。

## CLI 配置与身份验证

支持的运行时平台为 macOS、Linux 和 WSL。原生 Windows 可以运行 PHP，但 CLI 无法在已保存的凭据上校验 Windows ACL。如果必须使用原生 Windows，请改用 WSL 或手动限制文件权限。

配置文件选择按以下顺序进行：

1. `--config PATH`
2. 当前工作目录中的`.geoflow.json`（当文件存在时）
3. `~/.config/geoflow/config.json`

对于每项设置，CLI 选项优先于环境变量，环境变量优先于所选配置文件。支持的环境变量为 `GEOFLOW_BASE_URL`、`GEOFLOW_TOKEN` 或 `GEOFLOW_API_TOKEN`、`GEOFLOW_TIMEOUT` 和 `GEOFLOW_ALLOW_INSECURE_HTTP`。

端点和凭据绑定规则会拒绝不安全的来源混合：

- 从当前目录的 `.geoflow.json` 隐式加载的端点必须使用同一文件中的令牌。
- `--base-url` 需要在同一次调用中提供令牌。建议优先使用 `--token-stdin`。
- `GEOFLOW_BASE_URL` 无法从配置文件中继承令牌。将其与 `GEOFLOW_TOKEN`、`GEOFLOW_API_TOKEN` 或 `--token-stdin` 搭配使用。
- 显式 `--config` 会选择受信任的配置文件。主目录配置文件也被视为受信任的配置文件。
- 远程 HTTP 异常属于启用它的端点源。使用 `--base-url` 或 `GEOFLOW_BASE_URL` 覆盖配置文件端点时，还需要该调用环境中的 `--allow-insecure-http` 或 `GEOFLOW_ALLOW_INSECURE_HTTP`。

在不泄露令牌的情况下检查已解析的来源：

```bash
bin/geoflow --config /path/to/profile.json config show
```

使用从 stdin 读取的令牌初始化配置文件：

```bash
bin/geoflow --token-stdin config init \
  --base-url https://geoflow.example.com \
  --file /path/to/profile.json
```

在没有 `--file` 或 `--config` 的情况下，`config init` 会写入 `~/.config/geoflow/config.json`。现有文件需要 `--force`。在 POSIX 系统上，CLI 以模式 `0600` 写入包含令牌的文件，并将默认配置目录限制为 `0700`。

## 首次登录

登录会在交互式终端中使用隐藏密码提示：

```bash
bin/geoflow login \
  --base-url https://geoflow.example.com \
  --username admin
```

对于非交互式密钥传递，请通过 stdin 提供一行内容：

```bash
bin/geoflow --config /path/to/profile.json login \
  --base-url https://geoflow.example.com \
  --username admin \
  --password-stdin \
  --force
```

调用方必须将受保护的密钥源连接到 stdin。不要在示例或命令历史记录中放置真实密码。为兼容性，`--token` 和 `--password` 仍会被接受，但会发出弃用警告，并计划在下一个主 CLI 版本中移除。

如果 URL 没有 scheme，CLI 会添加 `https://`。对于 `localhost`、`*.localhost`、`127.0.0.0/8` 和 `::1`，允许使用回环 HTTP。其他 HTTP 目标需要显式的 `--allow-insecure-http`；仅对已批准的测试主机使用该例外。HTTPS 证书验证保持启用，不跟随 API 重定向，并且 CLI 请求 identity 编码，因此其 5 MiB 响应限制在解压缩之前生效。压缩的 API 响应会被拒绝。

### 仅 API 回退

当 `bin/geoflow` 不存在时，以下交互式流程会将密码、响应令牌和授权头排除在进程参数和标准输出之外。它应用下文使用的 API 回退策略：缺少方案时使用 HTTPS，拒绝远程 HTTP，并允许回环 HTTP。

在当前 Bash shell 中运行一次此受保护的传输设置。它将 curl 的标准输出通过 Python 流式处理，在每种 curl 版本上最多向受保护的响应文件写入 5 MiB，拒绝重定向/输出覆盖，并递归地脱敏错误输出：

```bash
geoflow_bounded_curl() {
  local output_path="$1" stderr_path="$2" max_bytes="$3"
  shift 3
  python3 - "$output_path" "$stderr_path" "$max_bytes" "$@" <<'PY'
import os
import pathlib
import re
import signal
import stat
import subprocess
import sys
import tempfile

output_path = pathlib.Path(sys.argv[1])
stderr_path = pathlib.Path(sys.argv[2])
max_bytes = int(sys.argv[3])
command = sys.argv[4:]
if max_bytes <= 0 or not command:
    raise SystemExit("Invalid bounded curl invocation")

blocked_long = ("--dump-header", "--include", "--location", "--location-trusted", "--output", "--write-out")
blocked_short = {"D", "i", "L", "o", "w"}
for argument in command[1:]:
    if argument in blocked_long or any(argument.startswith(option + "=") for option in blocked_long):
        raise SystemExit(f"Bounded curl forbids caller-controlled option: {argument}")
    if argument.startswith("-") and not argument.startswith("--") and any(flag in argument[1:] for flag in blocked_short):
        raise SystemExit(f"Bounded curl forbids caller-controlled short option: {argument}")
if "--disable" not in command or "--globoff" not in command:
    raise SystemExit("Bounded curl requires --disable and --globoff")
for path in (output_path, stderr_path):
    if not stat.S_ISREG(path.lstat().st_mode):
        raise SystemExit(f"Bounded curl output must be a regular file: {path}")

header_handle = tempfile.NamedTemporaryFile(prefix="geoflow-curl-headers-", delete=False)
header_path = pathlib.Path(header_handle.name)
header_handle.close()
os.chmod(header_path, 0o600)
command = [command[0], "--dump-header", str(header_path), *command[1:]]
process = None
try:
    with output_path.open("wb") as output_handle, stderr_path.open("wb") as error_handle:
        process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=error_handle)
        assert process.stdout is not None
        written = 0
        while True:
            chunk = process.stdout.read(65536)
            if not chunk:
                break
            remaining = max_bytes - written
            if len(chunk) > remaining:
                if remaining > 0:
                    output_handle.write(chunk[:remaining])
                process.stdout.close()
                process.terminate()
                try:
                    process.wait(timeout=2)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait()
                print(f"Response exceeded {max_bytes} bytes; curl was stopped.", file=sys.stderr)
                raise SystemExit(63)
            output_handle.write(chunk)
            written += len(chunk)
        process.stdout.close()
        return_code = process.wait()
        if return_code != 0:
            raise SystemExit(return_code)

    statuses = re.findall(rb"(?mi)^HTTP/[^\s]+\s+(\d{3})\b", header_path.read_bytes())
    if not statuses:
        raise SystemExit("Curl completed without an HTTP status line")
    print(statuses[-1].decode("ascii"))
finally:
    if process is not None and process.poll() is None:
        process.send_signal(signal.SIGTERM)
        try:
            process.wait(timeout=2)
        except subprocess.TimeoutExpired:
            process.kill()
            process.wait()
    try:
        header_path.unlink()
    except FileNotFoundError:
        pass
PY
}

geoflow_print_safe_files() {
  python3 - "$@" <<'PY'
import json
import os
import pathlib
import re
import sys
import unicodedata

sensitive_fragments = ("authorization", "password", "secret", "token", "api_key", "api-key", "apikey")
environment_secrets = [
    value for name in ("GEOFLOW_TOKEN", "GEOFLOW_API_TOKEN")
    if (value := os.environ.get(name))
]

def sanitize_text(value):
    value = "".join(char for char in value if unicodedata.category(char) not in {"Cc", "Cf"})
    for secret in environment_secrets:
        value = value.replace(secret, "[redacted]")
    value = re.sub(r"(?i)\bBearer\s+[^\s,;<>&]+", "Bearer [redacted]", value)
    return re.sub(
        r'''(?i)\b(authorization|password|secret|token|api[_-]?key|apikey)\b(\s*[:=]\s*)(?:"[^"\r\n]*"|'[^'\r\n]*'|[^\s,;<>&]+)''',
        lambda match: match.group(1) + match.group(2) + "[redacted]",
        value,
    )

def sanitize(value, key=""):
    if any(fragment in key.lower() for fragment in sensitive_fragments):
        return "[redacted]"
    if isinstance(value, str):
        return sanitize_text(value)
    if isinstance(value, list):
        return [sanitize(item) for item in value]
    if isinstance(value, dict):
        return {str(item_key): sanitize(item, str(item_key)) for item_key, item in value.items()}
    return value

for filename in sys.argv[1:]:
    path = pathlib.Path(filename)
    if not path.is_file() or path.stat().st_size == 0:
        continue
    text = path.read_text(encoding="utf-8", errors="replace")[:65536]
    try:
        rendered = json.dumps(sanitize(json.loads(text)), ensure_ascii=False, indent=2)
    except json.JSONDecodeError:
        rendered = sanitize_text(text)
    print(rendered[:800], file=sys.stderr)
PY
}
```

然后运行受保护的登录流程：

```bash
set -euo pipefail

: "${GEOFLOW_BASE_URL:?Set GEOFLOW_BASE_URL to the GEOFlow web root}"

geoflow_login_request="$(mktemp)"
geoflow_login_response="$(mktemp)"
geoflow_login_error="$(mktemp)"
geoflow_auth_header="$(mktemp)"
chmod 600 "$geoflow_login_request" "$geoflow_login_response" "$geoflow_login_error" "$geoflow_auth_header"
trap 'rm -f "$geoflow_login_request" "$geoflow_login_response" "$geoflow_login_error" "$geoflow_auth_header"' EXIT

geoflow_login_policy="$(python3 - "$GEOFLOW_BASE_URL" <<'PY'
import ipaddress
import sys
from urllib.parse import urlsplit

value = sys.argv[1]
if "://" not in value:
    value = "https://" + value
try:
    parsed = urlsplit(value)
    _ = parsed.port
except ValueError as exc:
    raise SystemExit(f"Invalid GEOFLOW_BASE_URL: {exc}")
if parsed.scheme not in {"http", "https"} or not parsed.hostname:
    raise SystemExit("GEOFLOW_BASE_URL must be an http(s) URL with a hostname")
if parsed.username is not None or parsed.password is not None or parsed.query or parsed.fragment:
    raise SystemExit("GEOFLOW_BASE_URL must not contain credentials, query, or fragment")
if any(char.isspace() for char in value):
    raise SystemExit("GEOFLOW_BASE_URL must not contain whitespace")
if "{" in value or "}" in value:
    raise SystemExit("GEOFLOW_BASE_URL must not contain curl glob characters: { or }")

hostname = parsed.hostname.rstrip(".").lower()
is_loopback = hostname == "localhost" or hostname.endswith(".localhost")
if not is_loopback:
    try:
        is_loopback = ipaddress.ip_address(hostname).is_loopback
    except ValueError:
        pass
if parsed.scheme == "http" and not is_loopback:
    raise SystemExit("Authenticated API fallback requires HTTPS unless the host is loopback")

print(value.rstrip("/") + "\t" + ("=http,https" if is_loopback else "=https"))
PY
)"
geoflow_login_base_url="${geoflow_login_policy%%$'\t'*}"
geoflow_login_proto="${geoflow_login_policy#*$'\t'}"

python3 - "$geoflow_login_request" <<'PY'
import getpass
import json
import pathlib
import sys

with open("/dev/tty", "r", encoding="utf-8") as terminal_input, open(
    "/dev/tty", "w", encoding="utf-8"
) as terminal_output:
    terminal_output.write("Admin username: ")
    terminal_output.flush()
    username = terminal_input.readline().strip()
    password = getpass.getpass("Admin password: ", stream=terminal_output)
if not username or not password:
    raise SystemExit("Username and password are required")
pathlib.Path(sys.argv[1]).write_text(
    json.dumps({"username": username, "password": password}) + "\n",
    encoding="utf-8",
)
PY

if ! geoflow_login_status="$(geoflow_bounded_curl \
  "$geoflow_login_response" "$geoflow_login_error" 5242880 \
  curl --disable --globoff --proto "$geoflow_login_proto" \
  --silent --show-error --max-time 20 --max-filesize 5242880 \
  --request POST -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data-binary "@$geoflow_login_request" \
  "$geoflow_login_base_url/api/v1/auth/login")"; then
  geoflow_print_safe_files "$geoflow_login_response" "$geoflow_login_error"
  echo "Protected GEOFlow login transport failed." >&2
  exit 1
fi
if [[ ! "$geoflow_login_status" =~ ^2[0-9][0-9]$ ]]; then
  geoflow_print_safe_files "$geoflow_login_response" "$geoflow_login_error"
  echo "Protected GEOFlow login returned HTTP $geoflow_login_status" >&2
  exit 1
fi

python3 - "$geoflow_login_response" "$geoflow_auth_header" <<'PY'
import json
import pathlib
import sys

try:
    payload = json.loads(pathlib.Path(sys.argv[1]).read_text(encoding="utf-8"))
except (OSError, UnicodeDecodeError, json.JSONDecodeError):
    raise SystemExit("Login response is not valid JSON")
data = payload.get("data") if isinstance(payload, dict) else None
token = data.get("token") if isinstance(data, dict) else None
if not isinstance(payload, dict) or payload.get("success") is not True or not isinstance(token, str) or not token.strip():
    raise SystemExit("Login response did not contain a successful token result")
token = token.strip()
if any(ord(char) < 32 or ord(char) == 127 for char in token):
    raise SystemExit("Login response contained an invalid token")
pathlib.Path(sys.argv[2]).write_text(
    "Authorization: Bearer " + token + "\n",
    encoding="utf-8",
)
PY

rm -f "$geoflow_login_request" "$geoflow_login_response" "$geoflow_login_error"
```

受保护标头一存在，请求、响应和 curl 错误文件就会被删除。请在同一 shell 中使用 `--header "@$geoflow_auth_header"` 进行后续 API 调用。shell 退出时，`EXIT` trap 会移除所有受保护文件，包括标头。不要打印或持久化响应正文或标头文件。

## CLI 0.2.0 命令参考

全局选项为 `--config PATH`、`--base-url URL`、`--token-stdin`、`--timeout SECONDS`、`--allow-insecure-http` 和 `--no-interaction`。`-h` 或 `--help` 打印帮助；`-V` 或 `--version` 打印 JSON 版本数据。`-q` 抑制正常输出，而重复使用 `-v` 会增加详细程度。

### 配置、登录与目录

| 命令 | 命令特定选项 |
|---|---|
| `config init` | `--base-url URL`, `--token-stdin`, `--file PATH`, `--force` |
| `config show` | 无；使用全局 `--config PATH` 选择配置文件 |
| `login` | `--base-url URL`, `--username USER`, `--password-stdin`, `--file PATH`, `--force` |
| `catalog` | 无 |

在变更之前，使用 `catalog` 作为已认证的读取：

```bash
bin/geoflow --config /path/to/profile.json catalog
```

### 任务与作业

| Command | 选项 |
|---|---|
| `task list` | `--page N`, `--per-page N`, `--status STATUS`, `--search TEXT` |
| `task create --json FILE` | `--idempotency-key KEY` |
| `task get TASK_ID` | 无 |
| `task update TASK_ID --json FILE` | `--idempotency-key KEY` |
| `task delete TASK_ID` | `--yes` |
| `task start TASK_ID` | `--enqueue-now`, `--idempotency-key KEY` |
| `task stop TASK_ID` | `--idempotency-key KEY` |
| `task enqueue TASK_ID` | `--job-type TYPE`, `--payload-json FILE`, `--idempotency-key KEY` |
| `task jobs TASK_ID` | `--status STATUS`, `--limit N` |
| `job get JOB_ID` | 无 |

示例：

```bash
bin/geoflow --config /path/to/profile.json task list --status active --per-page 20
bin/geoflow --config /path/to/profile.json task create --json ./task.json --idempotency-key task-create-001
bin/geoflow --config /path/to/profile.json task update 12 --json ./task-patch.json --idempotency-key task-update-12
bin/geoflow --config /path/to/profile.json task start 12 --enqueue-now --idempotency-key task-start-12
bin/geoflow --config /path/to/profile.json task enqueue 12 --job-type generate --payload-json ./job.json --idempotency-key task-enqueue-12
bin/geoflow --config /path/to/profile.json task jobs 12 --limit 20
bin/geoflow --config /path/to/profile.json job get 88
bin/geoflow --config /path/to/profile.json task delete 12
```

创建任务需要 `name`、`title_library_id`、`prompt_id` 和 `ai_model_id`。`knowledge_base_ids` 最多接受五个 ID，且优先于旧版 `knowledge_base_id`。API v1 不绑定 `distribution_channel_ids`；请使用已认证的管理员任务表单进行具体的渠道选择。

### 材料

支持的类型为 `categories`、`authors`、`keyword-libraries`、`title-libraries`、`image-libraries` 和 `knowledge-bases`。别名为 `keywords`、`titles`、`images` 和 `knowledge`。

| Command | 选项 |
|---|---|
| `material summary` | 无 |
| `material list TYPE` | `--page N`, `--per-page N`, `--search TEXT` |
| `material create TYPE --json FILE` | `--idempotency-key KEY` |
| `material get TYPE ID` | 无 |
| `material update TYPE ID --json FILE` | `--idempotency-key KEY` |
| `material delete TYPE ID` | `--yes` |
| `material item-list TYPE ID` | `--page N`, `--per-page N` |
| `material item-create TYPE ID --json FILE` | `--idempotency-key KEY` |
| `material item-upload TYPE ID --image FILE` | `--idempotency-key KEY`；仅 `image-libraries` 或 `images` |
| `material item-delete TYPE ID` | `--ids 1,2` 或 `--json FILE` 中恰好一个，加上可选的 `--yes` |

示例：

```bash
bin/geoflow --config /path/to/profile.json material summary
bin/geoflow --config /path/to/profile.json material list keyword-libraries --search geo --per-page 20
bin/geoflow --config /path/to/profile.json material create keyword-libraries --json ./library.json --idempotency-key library-create-001
bin/geoflow --config /path/to/profile.json material item-create keywords 12 --json ./keyword.json --idempotency-key keyword-create-001
bin/geoflow --config /path/to/profile.json material item-upload images 9 --image ./cover.png --idempotency-key image-upload-001
bin/geoflow --config /path/to/profile.json material item-delete titles 34 --ids 101,102
bin/geoflow --config /path/to/profile.json material delete keyword-libraries 12
```

知识库条目是生成的分块，通过条目命令只能读取。请更新父级知识库内容以重建分块。

### 文章

| Command | 选项 |
|---|---|
| `article list` | `--page N`, `--per-page N`, `--task-id ID`, `--status STATUS`, `--review-status STATUS`, `--author-id ID`, `--search TEXT` |
| `article create` | `--json FILE`，或下面列出的直接字段；`--idempotency-key KEY` |
| `article get ARTICLE_ID` | 无 |
| `article update ARTICLE_ID` | `--json FILE`，或下面列出的直接字段；`--idempotency-key KEY` |
| `article review ARTICLE_ID --status STATUS` | `--note TEXT`, `--risk-override-reason TEXT`, `--idempotency-key KEY` |
| `article publish ARTICLE_ID` | `--idempotency-key KEY` |
| `article trash ARTICLE_ID` | `--idempotency-key KEY` |

文章创建和更新共享`--title`、`--excerpt`、`--slug`、`--keywords`、`--meta-description`、`--task-id`、`--author-id`、`--category-id`、`--content`和`--content-file`。创建还接受`--status`、`--review-status`和`--ai-generated`；更新会在发送请求前拒绝这些工作流字段。使用`article review`、`article publish`或`article trash`进行工作流转换。创建要求非空标题和内容。`--content`和`--content-file`互斥。当`--json`存在时，JSON 对象提供请求体。

```bash
bin/geoflow --config /path/to/profile.json article list --task-id 12 --review-status pending --per-page 20
bin/geoflow --config /path/to/profile.json article create \
  --title "Article title" \
  --content-file ./article.md \
  --author-id 5 \
  --category-id 2 \
  --idempotency-key article-create-001
bin/geoflow --config /path/to/profile.json article update 101 --json ./article-patch.json --idempotency-key article-update-101
bin/geoflow --config /path/to/profile.json article review 101 --status approved --note "Reviewed" --idempotency-key article-review-101
bin/geoflow --config /path/to/profile.json article publish 101 --idempotency-key article-publish-101
bin/geoflow --config /path/to/profile.json article get 101
bin/geoflow --config /path/to/profile.json article trash 101 --idempotency-key article-trash-101
```

发布后，再次读取文章并使用持久化的 `/article/{slug}` URL。不要报告旧版 `article.php?id=...` 兼容形式。

## JSON 与标准输入

`--json FILE` 和 `--payload-json FILE` 读取一个 JSON 对象。使用 `-` 从标准输入读取该对象：

```bash
bin/geoflow --config /path/to/profile.json task create --json - --idempotency-key task-create-stdin
```

`--content-file -` 从标准输入读取文章文本。JSON 和文本输入限制为 5 MiB。机密标准输入标志读取一行，限制为 64 KiB。一个进程只有一个标准输入流，因此当两个选项都需要消费它时，不要组合使用 `--token-stdin`、`--password-stdin`、`--json -`、`--payload-json -` 或 `--content-file -`。

## 删除与幂等性

`task delete`、`material delete` 和 `material item-delete` 会在交互式终端中请求确认。非交互式运行必须包含 `--yes`。该标志仅确认确切的本地 CLI 目标；它不会绕过令牌作用域、服务器授权、锁、影响检查或仅限管理员的确认。

DELETE 操作不使用 `X-Idempotency-Key`。此规则适用于 CLI 和 API fallback 中的任务删除、素材删除和素材项删除。对于暴露幂等键的 POST 和 PATCH 操作，请使用幂等键，包括创建、更新、任务操作、文章审核/发布/移入回收站以及图片上传。

## API v1 回退

仅在 `bin/geoflow` 不存在时才使用 API 回退。附加 bearer token 之前验证基础 URL。已认证的非回环 HTTP 会被阻止；对于远程主机使用 HTTPS。

首先在当前 Bash shell 中运行上述仅 API 登录回退方案中的受保护传输设置。然后验证端点，创建受保护的请求头，并定义请求包装器。该包装器将 curl 响应和错误数据保存在 mode-`0600` 临时文件中，对每个 curl 版本强制执行 5 MiB 的流式上限，递归脱敏失败响应体，并且仅在其成为成功的 JSON 信封后才将响应写入 stdout：

```bash
: "${GEOFLOW_BASE_URL:?Set GEOFLOW_BASE_URL to the GEOFlow web root}"
: "${GEOFLOW_API_TOKEN:?Set GEOFLOW_API_TOKEN}"

geoflow_api_policy="$(python3 - "$GEOFLOW_BASE_URL" <<'PY'
import ipaddress
import sys
from urllib.parse import urlsplit

value = sys.argv[1]
if "://" not in value:
    value = "https://" + value
try:
    parsed = urlsplit(value)
    _ = parsed.port
except ValueError as exc:
    raise SystemExit(f"Invalid GEOFLOW_BASE_URL: {exc}")
if parsed.scheme not in {"http", "https"} or not parsed.hostname:
    raise SystemExit("GEOFLOW_BASE_URL must be an http(s) URL with a hostname")
if parsed.username is not None or parsed.password is not None or parsed.query or parsed.fragment:
    raise SystemExit("GEOFLOW_BASE_URL must not contain credentials, query, or fragment")
if any(char.isspace() for char in value):
    raise SystemExit("GEOFLOW_BASE_URL must not contain whitespace")
if "{" in value or "}" in value:
    raise SystemExit("GEOFLOW_BASE_URL must not contain curl glob characters: { or }")

hostname = parsed.hostname.rstrip(".").lower()
is_loopback = hostname == "localhost" or hostname.endswith(".localhost")
if not is_loopback:
    try:
        is_loopback = ipaddress.ip_address(hostname).is_loopback
    except ValueError:
        pass
if parsed.scheme == "http" and not is_loopback:
    raise SystemExit("Authenticated API fallback requires HTTPS unless the host is loopback")

print(value.rstrip("/") + "\t" + ("=http,https" if is_loopback else "=https"))
PY
)"
geoflow_api_base_url="${geoflow_api_policy%%$'\t'*}"
geoflow_api_proto="${geoflow_api_policy#*$'\t'}"

geoflow_auth_header="$(mktemp)"
chmod 600 "$geoflow_auth_header"
python3 - "$geoflow_auth_header" <<'PY'
import os
import pathlib
import sys

token = os.environ.get("GEOFLOW_API_TOKEN", "").strip()
if not token:
    raise SystemExit("GEOFLOW_API_TOKEN must not be empty")
if any(ord(char) < 32 or ord(char) == 127 for char in token):
    raise SystemExit("GEOFLOW_API_TOKEN contains invalid control characters")
pathlib.Path(sys.argv[1]).write_text(
    "Authorization: Bearer " + token + "\n",
    encoding="utf-8",
)
PY
trap 'rm -f "$geoflow_auth_header"' EXIT

geoflow_api_request() (
  set -euo pipefail
  local geoflow_api_response geoflow_api_error geoflow_api_status
  geoflow_api_response="$(mktemp)"
  geoflow_api_error="$(mktemp)"
  chmod 600 "$geoflow_api_response" "$geoflow_api_error"
  trap 'rm -f "$geoflow_api_response" "$geoflow_api_error"' EXIT

  if ! geoflow_api_status="$(geoflow_bounded_curl \
    "$geoflow_api_response" "$geoflow_api_error" 5242880 \
    curl --disable --globoff --proto "$geoflow_api_proto" \
    --silent --show-error --max-time 20 --max-filesize 5242880 \
    "$@")"; then
    geoflow_print_safe_files "$geoflow_api_response" "$geoflow_api_error"
    echo "API fallback transport failed." >&2
    exit 1
  fi

  if [[ ! "$geoflow_api_status" =~ ^2[0-9][0-9]$ ]]; then
    geoflow_print_safe_files "$geoflow_api_response" "$geoflow_api_error"
    echo "API fallback returned HTTP $geoflow_api_status." >&2
    exit 1
  fi

  if ! python3 - "$geoflow_api_response" <<'PY'
import json
import pathlib
import sys

try:
    payload = json.loads(pathlib.Path(sys.argv[1]).read_text(encoding="utf-8"))
except (OSError, UnicodeDecodeError, json.JSONDecodeError):
    raise SystemExit(1)
if not isinstance(payload, dict) or payload.get("success") is not True:
    raise SystemExit(1)
PY
  then
    geoflow_print_safe_files "$geoflow_api_response" "$geoflow_api_error"
    echo "API fallback response was not a successful JSON envelope." >&2
    exit 1
  fi

  cat "$geoflow_api_response"
)
```

每个请求都必须使用包装器和 `Accept: application/json`。仅传递特定于请求的 curl 选项；包装器提供协议策略、超时、响应限制、受保护文件和重定向阻止：

```bash
geoflow_api_request \
  --header "@$geoflow_auth_header" \
  -H 'Accept: application/json' \
  "$geoflow_api_base_url/api/v1/catalog"
```

端点校验仅对解析后的回环主机名选择 HTTP。绝不能仅凭字符串前缀推断回环安全性。不要将 curl 输出、header-dump、include、write-out 或重定向选项传给 `geoflow_api_request`；其有界传输会拒绝这些覆盖。

API v1 直接映射到 CLI 操作：

| 区域 | API 路径 |
|---|---|
| 认证 | `POST /api/v1/auth/login` |
| 目录 | `GET /api/v1/catalog` |
| 任务 | `GET|POST /api/v1/tasks`, `GET|PATCH|DELETE /api/v1/tasks/{id}`, `POST /start`, `POST /stop`, `POST /enqueue`, `GET /jobs` |
| 任务 | `GET /api/v1/jobs/{id}` |
| 材料 | `GET /api/v1/materials`、类型化 `GET|POST`、类型化项 `GET|POST|DELETE`、类型化父项 `GET|PATCH|DELETE` |
| 文章 | `GET|POST /api/v1/articles`、`GET|PATCH /api/v1/articles/{id}`、`POST /review`、`POST /publish`、`POST /trash` |

示例任务创建：

```bash
geoflow_api_request --request POST \
  --header "@$geoflow_auth_header" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Idempotency-Key: task-create-001' \
  --data-binary @./task.json \
  "$geoflow_api_base_url/api/v1/tasks"
```

示例任务删除，不带幂等性标头：

```bash
geoflow_api_request --request DELETE \
  --header "@$geoflow_auth_header" \
  -H 'Accept: application/json' \
  "$geoflow_api_base_url/api/v1/tasks/12"
```

HTML 响应通常意味着 base URL、代理或 Laravel 路由有误。将 `401` 归类为无效或过期凭据，将 `403` 归类为缺少作用域，将 `423` 归类为资源已锁定，将 `429` 归类为速率限制。在诊断中保留 JSON 错误代码和 `retry_after` 值，但不要打印机密信息。

## 管理后台 Web 边界

管理后台前缀来自 `ADMIN_BASE_PATH` 或 `geoflow.admin_base_path`；仓库默认值为 `geo_admin`。请将下面的 `{admin}` 替换为目标部署经检查得到的前缀。管理后台写入需要当前会话 cookie、CSRF token、路由特定的表单数据，以及任何超级管理员或当前密码检查。

当前仅管理员可用的组包括：

- 分析页面：`GET /{admin}/analytics`、`/content`、`/traffic`、`/ai-visibility`、`/leads` 以及超级管理员 `/distribution`。
- 手动发布工作台：路由位于 `/{admin}/manual-publications` 下，并提供用于人设和账户引用的超级管理员设置。
- 文章编辑器辅助和风险检查：`GET /{admin}/articles/editor/titles`、`POST /{admin}/articles/editor/generate` 和 `POST /{admin}/articles/{articleId}/risk-scan`。
- AI 来源提供者：`/{admin}/ai-source-providers` 下的路由，包括提供者和模型绑定测试。
- 分发渠道：`/{admin}/distribution` 下的路由，包括预览、准备、取消和最终渠道删除流程。
- 首页模块编辑器：`GET /{admin}/site-settings/homepage-modules`，带有 POST 更新、预设和导入路由。
- 企业知识、线索表单和线索、URL 导入、系统更新、主题复制、站点/安全设置、管理员用户、活动日志和 API 令牌管理。

当前路由文件没有可用的 Theme Editor 路由。除非在目标部署上进行路由发现找到明确路由，否则报告为不可用。有关授权和风险边界，请参阅 [GEOFlow 当前能力地图](geoflow-current-capability-map.md) 和 [操作边界](operation-boundary.md)。
