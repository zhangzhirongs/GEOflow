# GEOFlow 部署脚本（Deployment Scripts）

这个目录用于存放 GEOFlow 的参考部署脚本，方便技术人员在常见云服务器、VPS、Docker 主机或面板服务器上快速完成环境自检和生产部署。

脚本默认走仓库现有的 `docker-compose.prod.yml` 生产链路，不绕开项目标准部署方式。

## 脚本清单

| 脚本 | 用途 |
| --- | --- |
| `geoflow-docker-deploy.sh` | 生产 Docker 首次空库一键部署脚本。会自检服务器、准备 `.env.prod`、部署 PostgreSQL、Redis、Web、App、队列、调度和 Reverb，并在最后执行健康检查。 |
| `geoflow-healthcheck.sh` | 部署后健康检查脚本。可单独检查容器状态、Laravel 健康端点和数据库连接。 |
| `start-docker-pull-tunnel.sh` | **本机 Mac**：SSH 反向隧道，把 Clash HTTP 代理暴露给 ECS。 |
| `pull-images-once-via-tunnel.sh` | **ECS 一次性拉镜像**：经隧道 + `skopeo`，**不重启 docker**，不影响运行中容器。 |
| `build-once-via-tunnel.sh` | **ECS 一次性 build**：临时代理仅作用于本次 `docker compose build`，**不重启 docker**。 |
| `sync-images-from-local.sh` | **本机 Mac**：本机 `docker pull` 后 `ssh docker load` 到 ECS，无需隧道、无需改 ECS Docker。 |

## 推荐服务器配置

测试最低配置：

- 2 核 CPU
- 2 GB 内存，建议额外配置 2 GB swap
- 20 GB 可用磁盘
- Ubuntu 22.04+ / Debian 12+ / Rocky Linux 9+ / 阿里云 Linux 3+
- 可以稳定访问 Docker 镜像源、GitHub 和你配置的 AI API 服务商

正式生产建议：

- 2-4 核 CPU
- 4-8 GB 内存
- 40-80 GB SSD
- 使用 Nginx、Caddy、宝塔、1Panel、SLB 或 CDN 做 HTTPS 反向代理
- PostgreSQL 和 Redis 不直接暴露到公网

## 一键部署

仅在全新空数据库的服务器执行：

```bash
curl -fsSL https://raw.githubusercontent.com/zhangzhirongs/GEOflow/main/deploy-scripts/geoflow-docker-deploy.sh -o geoflow-docker-deploy.sh
bash geoflow-docker-deploy.sh
```

已有数据的实例禁止使用该脚本升级，也禁止滚动升级。请执行 `docs/deployment/DEPLOYMENT.md` 3.1 节的 down、停止并排空全部旧进程和在途请求、一次性确认、迁移、全量启动新版本、readiness、启用删除门禁流程。

脚本会要求确认：

- 对外访问的 `APP_URL`
- Web 端口，默认 `18080`
- Reverb 端口，默认 `18081`
- 后台入口路径，默认 `geo_admin`

部署完成后：

- 前台：`APP_URL`
- 后台：`APP_URL/geo_admin/login`
- 默认管理员：`admin`
- 默认密码：`password`

首次登录后请立即修改默认密码。

## 非交互部署

适合云服务器初始化脚本、镜像模板或 CI：

```bash
GEOFLOW_NONINTERACTIVE=1 \
GEOFLOW_APP_URL=https://example.com \
GEOFLOW_APP_DIR=/opt/geoflow \
GEOFLOW_WEB_PORT=18080 \
GEOFLOW_REVERB_PORT=18081 \
GEOFLOW_ADMIN_BASE_PATH=geo_admin \
bash geoflow-docker-deploy.sh
```

常用变量：

| 变量 | 默认值 | 说明 |
| --- | --- | --- |
| `GEOFLOW_REPO_URL` | `https://github.com/zhangzhirongs/GEOflow.git` | 源码仓库地址 |
| `GEOFLOW_BRANCH` | `main` | 部署分支 |
| `GEOFLOW_APP_DIR` | `/opt/geoflow` | 服务器部署目录 |
| `GEOFLOW_INSTALL_DOCKER` | `auto` | `1` 自动安装 Docker；`0` 缺少 Docker 时直接失败 |
| `GEOFLOW_DB_PASSWORD` | 随机生成 | PostgreSQL 密码 |
| `GEOFLOW_REDIS_PASSWORD` | 随机生成 | Redis 密码 |
| `GEOFLOW_TRUSTED_PROXIES` | 留空 | 反向代理、CDN、二级目录部署时填写实际代理 IP/CIDR |
| `GEOFLOW_SESSION_SECURE_COOKIE` | 根据 `APP_URL` 协议自动设置 | HTTPS 为 `true`；直接 HTTP 访问为 `false` |
| `GEOFLOW_SELF_DELETE` | `0` | 设置为 `1` 时，部署成功后删除当前执行的部署脚本 |

## 执行后自删除

如果你把部署脚本下载到临时目录，部署成功后希望自动删除它：

```bash
GEOFLOW_SELF_DELETE=1 bash geoflow-docker-deploy.sh
```

这个动作只会删除当前执行的脚本文件，不会删除已部署的 GEOFlow 源码目录。

## 手动健康检查

部署后、改域名、改 HTTPS 或改反向代理后，可以执行：

```bash
cd /opt/geoflow
bash deploy-scripts/geoflow-healthcheck.sh
```

## 一级目录部署

如果网站部署在一级目录下，例如：

```text
https://example.com/wiki
```

建议配置：

```env
APP_URL=https://example.com/wiki
TRUSTED_PROXIES=203.0.113.10
ADMIN_BASE_PATH=geo_admin
```

不要把 `ADMIN_BASE_PATH` 写成 `wiki/geo_admin`。一级目录应由反向代理处理，并透传：

```nginx
proxy_set_header X-Forwarded-Prefix /wiki;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-Host $host;
```

## 注意事项

- 脚本是部署辅助工具，不替代服务器安全加固。
- 不要把 PostgreSQL 和 Redis 暴露到公网。
- 更新前请备份 `.env.prod`、`storage/` 和 PostgreSQL 数据。
- 如果大陆服务器拉取 Docker 镜像较慢，建议先在 Docker daemon 层配置稳定镜像源，再执行脚本。

---

此文件夹包含参考脚本，供希望获得更快、可重复的 GEOFlow 部署路径的技术操作人员使用。

## 脚本

| 脚本 | 用途 |
| --- | --- |
| `geoflow-docker-deploy.sh` | 在全新的空生产数据库上首次安装。它会检查服务器、准备 `.env.prod`、部署 PostgreSQL、Redis、web、app、queue、scheduler 和 Reverb，然后运行健康检查。 |
| `geoflow-healthcheck.sh` | 部署后健康检查。它验证 Docker Compose 服务、Laravel 健康端点和数据库连通性。 |

## 推荐的服务器配置档案

测试最低要求：

- 2 vCPU
- 2 GB 内存加 2 GB swap
- 20 GB 可用磁盘空间
- Ubuntu 22.04+/Debian 12+/Rocky Linux 9+/阿里云 Linux 3+
- 稳定出站网络访问 Docker registry、GitHub 以及你的 AI 提供商 API

生产环境推荐：

- 2-4 vCPU
- 4-8 GB 内存
- 40-80 GB SSD
- 用于 HTTPS 的反向代理或云负载均衡器
- PostgreSQL 和 Redis 端口未暴露到公共互联网

## 单命令首次安装

在数据库为空的全新服务器上，运行：

```bash
curl -fsSL https://raw.githubusercontent.com/zhangzhirongs/GEOflow/main/deploy-scripts/geoflow-docker-deploy.sh -o geoflow-docker-deploy.sh
bash geoflow-docker-deploy.sh
```

不要使用此脚本升级现有部署，也不要执行滚动升级。遵循 `docs/deployment/DEPLOYMENT.md` 第 3.1 节中的停止并排空升级协议。

脚本将询问：

- 公共 `APP_URL`
- Web 端口，默认 `18080`
- Reverb 端口，默认 `18081`
- 管理端基础路径，默认 `geo_admin`

部署之后：

- 站点：`APP_URL`
- 管理端：`APP_URL/geo_admin/login`
- 默认用户名：`admin`
- 默认密码：`password`

首次登录后立即更改默认管理员密码。

## 非交互式部署

对于 CI、镜像模板或脚本化服务器初始化：

```bash
GEOFLOW_NONINTERACTIVE=1 \
GEOFLOW_APP_URL=https://example.com \
GEOFLOW_APP_DIR=/opt/geoflow \
GEOFLOW_WEB_PORT=18080 \
GEOFLOW_REVERB_PORT=18081 \
GEOFLOW_ADMIN_BASE_PATH=geo_admin \
bash geoflow-docker-deploy.sh
```

可选变量：

| 变量 | 默认值 | 描述 |
| --- | --- | --- |
| `GEOFLOW_REPO_URL` | `https://github.com/zhangzhirongs/GEOflow.git` | 源仓库 URL |
| `GEOFLOW_BRANCH` | `main` | 要部署的分支 |
| `GEOFLOW_APP_DIR` | `/opt/geoflow` | 服务器安装目录 |
| `GEOFLOW_INSTALL_DOCKER` | `auto` | `1` 表示自动安装 Docker，`0` 表示在缺少 Docker 时失败 |
| `GEOFLOW_DB_PASSWORD` | 随机 | PostgreSQL 密码 |
| `GEOFLOW_REDIS_PASSWORD` | 随机 | Redis 密码 |
| `GEOFLOW_TRUSTED_PROXIES` | 空 | 对于反向代理/CDN/子目录部署，设置为实际的代理 IP/CIDR |
| `GEOFLOW_SESSION_SECURE_COOKIE` | 派生自 `APP_URL` | `true` 用于 HTTPS，`false` 用于直接 HTTP 访问 |
| `GEOFLOW_SELF_DELETE` | `0` | 设置为 `1` 以在成功部署后移除部署脚本 |

## 自删除模式

如果你将脚本下载到临时位置，并希望在部署后将其删除：

```bash
GEOFLOW_SELF_DELETE=1 bash geoflow-docker-deploy.sh
```

这仅移除已执行的脚本文件。它不会移除已部署的 GEOFlow 源代码。

## 手动健康检查

在 DNS、HTTPS 或反向代理变更后运行：

```bash
cd /opt/geoflow
bash deploy-scripts/geoflow-healthcheck.sh
```

## 子目录部署

如果站点部署在一级路径下，例如：

```text
https://example.com/wiki
```

使用：

```env
APP_URL=https://example.com/wiki
TRUSTED_PROXIES=203.0.113.10
ADMIN_BASE_PATH=geo_admin
```

不要设置 `ADMIN_BASE_PATH=wiki/geo_admin`。反向代理应正确剥离或转发该前缀，并传递：

```nginx
proxy_set_header X-Forwarded-Prefix /wiki;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-Host $host;
```

## 说明

- 这些脚本仅供参考，不能替代服务器安全加固。
- 不要将 PostgreSQL 或 Redis 暴露到公共互联网。
- 升级前确保 `.env.prod`、`storage/` 和 PostgreSQL 数据已备份。
- 如果在中国大陆拉取 Docker 镜像缓慢或不稳定，请使用下方的一次性隧道脚本（无需 `docker restart`），或在 `daemon.json` 中配置镜像仓库镜像。

## 国内一次性拉镜像（不重启 Docker、不中断服务）

改 `/etc/systemd/system/docker.service.d/` 并 `systemctl restart docker` **会短暂影响所有容器**。推荐用下面两种方式之一：

### 方式 A：隧道 + skopeo（ECS 拉，走你本机网络）

**终端 1（Mac，保持不关）：**

```bash
REMOTE_PROXY_PORT=1080 LOCAL_PROXY_PORT=1082 \
ECS_HOST=ecs-user@you-ip \
bash deploy-scripts/start-docker-pull-tunnel.sh
```

**终端 2（ECS）：**

```bash
DOCKER_TUNNEL_PROXY_PORT=1080 bash deploy-scripts/pull-images-once-via-tunnel.sh --env-file .env.prod
DOCKER_TUNNEL_PROXY_PORT=1080 bash deploy-scripts/build-once-via-tunnel.sh --env-file .env.prod
sudo docker-compose --env-file .env.prod -f docker-compose.prod.yml up -d
```

- `skopeo` 只在本进程走 `HTTP_PROXY`，**不动 Docker daemon**
- `build-once-via-tunnel.sh` 用临时 `DOCKER_CONFIG`，仅本次 build 走代理
- `up -d` 若镜像已齐，通常不再拉取

### 方式 B：本机拉取 + 管道导入（更简单，不用隧道）

```bash
ECS_HOST=ecs-user@你的IP bash deploy-scripts/sync-images-from-local.sh
```

本机用 Clash 拉镜像，经 SSH `docker save | docker load` 灌进 ECS。ECS 零配置。

Apple Silicon 本机默认拉取 `linux/amd64`（脚本已设置 `DOCKER_PLATFORM=linux/amd64`）。
