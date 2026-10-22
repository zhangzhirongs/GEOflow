# Xiaohongshu 发布器网关

用于 GEOFlow 小红书账号登录和发布的本地 Playwright 网关。

## 开始

```bash
cd tools/xhs-publisher-gateway
npm install
npm run install:browsers
npm start
```

默认 URL：

```text
http://127.0.0.1:8787
```

当 GEOFlow 在 Windows 或 macOS 上的 Docker 中运行时，将 GEOFlow 账户发布端点配置为：

```text
http://host.docker.internal:8787/xhs/publish
```

并在 `.env` 中允许本地网关：

```dotenv
GEOFLOW_OUTBOUND_PRIVATE_TARGETS=host.docker.internal:8787
```

更改 `.env` 后重启 GEOFlow Docker 应用。

## GEOFlow 账户设置

在 `manual-publications/settings` 中：

- 平台：`xiaohongshu`
- 适配器：空或 `xiaohongshu_login_session`
- 网关账户 ID：一个稳定的 ID，例如 `xhs-account-001`
- 发布网关 URL：`http://host.docker.internal:8787/xhs/publish`
- 登录标识符：账户昵称或备注
- 会话字符串：留空

然后为账户点击 `登录/刷新`。网关会打开浏览器窗口。请手动登录。登录后点击 `检查状态`。

## 端点

```text
POST /xhs/login/start
POST /xhs/login/status
POST /xhs/publish
```

网关将 Playwright 存储状态存储在：

```text
tools/xhs-publisher-gateway/storage/states
```

不要提交此目录。它包含敏感的登录状态。

## 发布行为

默认情况下，网关会打开 Xiaohongshu 发布页面并准备草稿。它不会点击发布。

仅在已验证选择器和工作流后再设置此项：

```bash
XHS_AUTO_CLICK_PUBLISH=true npm start
```

小红书页面结构可能变化。如果填充失败，请更新 `src/server.js` 中的选择器。
