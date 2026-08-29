import { defineConfig, devices } from '@playwright/test';

const PHP_BIN = 'C:/laragon/bin/php/php-8.3.16-Win32-vs16-x64/php.exe';
const PORT = 8129;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list']],
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile-chrome', use: { ...devices['Pixel 7'] } },
    ],
    webServer: {
        command: `"${PHP_BIN}" artisan serve --port=${PORT}`,
        url: `http://127.0.0.1:${PORT}`,
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
