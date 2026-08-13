# Xiaohongshu Publisher Gateway

Local Playwright gateway for GEOFlow Xiaohongshu account login and publishing.

## Start

```bash
cd tools/xhs-publisher-gateway
npm install
npm run install:browsers
npm start
```

Default URL:

```text
http://127.0.0.1:8787
```

When GEOFlow runs in Docker on Windows or macOS, configure the GEOFlow account publish endpoint as:

```text
http://host.docker.internal:8787/xhs/publish
```

and allow the local gateway in `.env`:

```dotenv
GEOFLOW_OUTBOUND_PRIVATE_TARGETS=host.docker.internal:8787
```

Restart the GEOFlow Docker app after changing `.env`.

## GEOFlow Account Settings

In `manual-publications/settings`:

- platform: `xiaohongshu`
- adapter: empty or `xiaohongshu_login_session`
- gateway account ID: a stable ID such as `xhs-account-001`
- publish gateway URL: `http://host.docker.internal:8787/xhs/publish`
- login identifier: account nickname or note
- session string: leave empty

Then click `登录/刷新` for the account. The gateway opens a browser window. Log in manually. Click `检查状态` after login.

## Endpoints

```text
POST /xhs/login/start
POST /xhs/login/status
POST /xhs/publish
```

The gateway stores Playwright storage state under:

```text
tools/xhs-publisher-gateway/storage/states
```

Do not commit this directory. It contains sensitive login state.

## Publish Behavior

By default the gateway opens the Xiaohongshu publish page and prepares a draft. It does not click publish.

Set this only after you have verified the selectors and workflow:

```bash
XHS_AUTO_CLICK_PUBLISH=true npm start
```

Xiaohongshu page structure can change. If filling fails, update selectors in `src/server.js`.
