import crypto from "node:crypto";
import fs from "node:fs/promises";
import path from "node:path";
import express from "express";
import { chromium } from "playwright";

const app = express();
const port = Number(process.env.XHS_GATEWAY_PORT || 8787);
const host = process.env.XHS_GATEWAY_HOST || "127.0.0.1";
const dataDir = path.resolve(process.env.XHS_GATEWAY_DATA_DIR || "./storage");
const stateDir = path.join(dataDir, "states");
const browserChannel = process.env.XHS_BROWSER_CHANNEL || "";
const loginUrl = process.env.XHS_LOGIN_URL || "https://www.xiaohongshu.com/explore";
const publishUrl = process.env.XHS_PUBLISH_URL || "https://creator.xiaohongshu.com/publish/publish";

app.use(express.json({ limit: "10mb" }));

await fs.mkdir(stateDir, { recursive: true });

app.post("/xhs/login/start", async (req, res) => {
  const account = accountPayload(req.body);
  const browser = await launchBrowser(false);
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(loginUrl, { waitUntil: "domcontentloaded" });

  const token = crypto.randomUUID();
  const statePath = stateFile(account.account_id);
  sessions.set(token, { browser, context, page, statePath, account });

  void waitForLoginAndPersist(token);

  res.json({
    status: "started",
    account_id: account.account_id,
    login_url: loginUrl,
    token,
  });
});

app.post("/xhs/login/status", async (req, res) => {
  const account = accountPayload(req.body);
  const exists = await fileExists(stateFile(account.account_id));
  if (!exists) {
    res.json({ status: "missing", account_id: account.account_id });
    return;
  }

  const ok = await verifyState(account.account_id);
  res.json({ status: ok ? "ready" : "expired", account_id: account.account_id });
});

app.post("/xhs/publish", async (req, res) => {
  const accountId = String(req.body?.gateway_account_id || req.body?.account_id || "").trim();
  if (!accountId) {
    res.status(422).json({ error: "account_id_required" });
    return;
  }
  const article = req.body?.payload?.article || {};
  const title = String(article.title || "").trim();
  const content = String(article.content || article.excerpt || "").trim();
  if (!title || !content) {
    res.status(422).json({ error: "title_and_content_required" });
    return;
  }
  if (!(await fileExists(stateFile(accountId)))) {
    res.status(409).json({ error: "login_state_missing", account_id: accountId });
    return;
  }

  const browser = await launchBrowser(process.env.XHS_PUBLISH_HEADLESS !== "false");
  const context = await browser.newContext({ storageState: stateFile(accountId) });
  const page = await context.newPage();
  try {
    await page.goto(publishUrl, { waitUntil: "domcontentloaded", timeout: 45000 });
    await fillFirst(page, [
      'input[placeholder*="标题"]',
      'textarea[placeholder*="标题"]',
      '[contenteditable="true"]',
    ], title);
    await fillFirst(page, [
      'textarea[placeholder*="正文"]',
      'textarea[placeholder*="内容"]',
      '[contenteditable="true"]',
    ], content, { skipFirstEditable: true });

    if (process.env.XHS_AUTO_CLICK_PUBLISH === "true") {
      await clickFirst(page, [
        'button:has-text("发布")',
        'button:has-text("提交")',
      ]);
    }

    await context.storageState({ path: stateFile(accountId) });
    res.json({
      status: process.env.XHS_AUTO_CLICK_PUBLISH === "true" ? "submitted" : "draft_prepared",
      remote_id: "",
      remote_url: page.url(),
    });
  } finally {
    await browser.close();
  }
});

const sessions = new Map();

async function waitForLoginAndPersist(token) {
  const session = sessions.get(token);
  if (!session) return;
  try {
    await session.page.waitForTimeout(Number(process.env.XHS_LOGIN_MIN_WAIT_MS || 15000));
    await session.context.storageState({ path: session.statePath });
  } catch {
    // The status endpoint will report missing or expired state when persistence fails.
  } finally {
    sessions.delete(token);
  }
}

function accountPayload(body) {
  const accountId = String(body?.account_id || "").trim();
  if (!accountId) {
    throw new Error("account_id_required");
  }
  return {
    account_id: accountId,
    login_identifier: String(body?.login_identifier || "").trim(),
    account_name: String(body?.account_name || "").trim(),
  };
}

async function launchBrowser(headless) {
  return chromium.launch({
    headless,
    channel: browserChannel || undefined,
  });
}

function stateFile(accountId) {
  const safe = accountId.replace(/[^a-zA-Z0-9._-]/g, "_");
  return path.join(stateDir, `${safe}.json`);
}

async function fileExists(file) {
  try {
    await fs.access(file);
    return true;
  } catch {
    return false;
  }
}

async function verifyState(accountId) {
  const browser = await launchBrowser(true);
  const context = await browser.newContext({ storageState: stateFile(accountId) });
  const page = await context.newPage();
  try {
    await page.goto(loginUrl, { waitUntil: "domcontentloaded", timeout: 30000 });
    const cookies = await context.cookies();
    return cookies.some((cookie) => cookie.domain.includes("xiaohongshu.com"));
  } finally {
    await browser.close();
  }
}

async function fillFirst(page, selectors, value, options = {}) {
  let editableSeen = 0;
  for (const selector of selectors) {
    const locator = page.locator(selector).first();
    if ((await locator.count()) === 0) continue;
    if (selector.includes("contenteditable")) {
      editableSeen += 1;
      if (options.skipFirstEditable && editableSeen === 1) continue;
    }
    await locator.fill(value, { timeout: 5000 }).catch(async () => {
      await locator.click({ timeout: 5000 });
      await page.keyboard.insertText(value);
    });
    return true;
  }
  return false;
}

async function clickFirst(page, selectors) {
  for (const selector of selectors) {
    const locator = page.locator(selector).first();
    if ((await locator.count()) === 0) continue;
    await locator.click({ timeout: 5000 });
    return true;
  }
  return false;
}

app.listen(port, host, () => {
  console.log(`XHS publisher gateway listening on http://${host}:${port}`);
});
