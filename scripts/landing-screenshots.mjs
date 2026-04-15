import { chromium } from 'playwright';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(__dirname, '..', 'public', 'landing', 'screenshots');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080';
const EMAIL = 'demo@alfahome.test';
const PASSWORD = 'password';

const HIDE_OVERLAYS_CSS = `
  #toast-indicacao,
  [x-data="pwaInstall()"],
  .install-prompt,
  .bottom-nav,
  .mobile-nav { display: none !important; }
`;

async function capture({ theme, route, filename }) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
  });

  await context.addInitScript((t) => {
    try {
      localStorage.setItem('alfahome-theme', t);
    } catch (e) {}
  }, theme);

  const page = await context.newPage();

  await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL('**/dashboard**', { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);
  await page.waitForLoadState('networkidle');

  if (route && !route.endsWith('/dashboard')) {
    await page.goto(`${BASE_URL}${route}`, { waitUntil: 'networkidle' });
  }

  await page.addStyleTag({ content: HIDE_OVERLAYS_CSS });

  await page.evaluate((t) => {
    document.body.classList.toggle('dark-mode', t === 'dark');
    document.documentElement.classList.toggle('ah-dark-preload', t === 'dark');
  }, theme);

  await page.evaluate(async () => {
    await Promise.all([
      document.fonts.load('900 16px "Font Awesome 7 Free"'),
      document.fonts.load('400 16px "Font Awesome 7 Free"'),
      document.fonts.load('400 16px "Font Awesome 7 Brands"'),
      document.fonts.load('900 16px "Font Awesome 5 Free"'),
    ]);
    await document.fonts.ready;
  });
  await page.waitForTimeout(1500);

  const file = path.join(outDir, filename);
  await page.screenshot({ path: file, fullPage: false });
  console.log(`saved ${file}`);

  await browser.close();
}

await capture({ theme: 'dark',  route: '/dashboard', filename: 'dashboard-dark.png' });
await capture({ theme: 'light', route: '/dashboard', filename: 'dashboard-light.png' });
await capture({ theme: 'dark',  route: '/despesas',  filename: 'despesas-dark.png' });
await capture({ theme: 'light', route: '/despesas',  filename: 'despesas-light.png' });
await capture({ theme: 'dark',  route: '/bancos',    filename: 'bancos-dark.png' });
await capture({ theme: 'light', route: '/bancos',    filename: 'bancos-light.png' });
await capture({ theme: 'dark',  route: '/investimentos', filename: 'investimentos-dark.png' });
await capture({ theme: 'light', route: '/investimentos', filename: 'investimentos-light.png' });
