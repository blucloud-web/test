import express from 'express';
import { loadNodeRuntime, useHostFilesystem } from '@php-wasm/node';
import { PHP, PHPRequestHandler } from '@php-wasm/universal';
import path from 'path';
import fs from 'fs';

const app = express();
const PORT = 3000;
const rootDir = process.cwd();

// Middleware برای پارس کردن بدنه درخواست‌ها
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// سرو فایل‌های استاتیک از پوشه اصلی و پوشه‌های دارایی‌ها
app.use('/assets', express.static(path.join(rootDir, 'assets')));
app.use('/uploads', express.static(path.join(rootDir, 'uploads')));

// پسوند‌های استاتیک که نباید به پی‌اچ‌پی ارسال شوند
const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];

async function bootstrap() {
    console.log('در حال راه‌اندازی محیط اجرایی PHP 8.3 (WASM)...');

    // لود کردن runtime پی‌اچ‌پی با مسیرهای صحیح فایل WASM
    const runtime = await loadNodeRuntime('8.3', {
        emscriptenOptions: {
            processId: 1,
            locateFile: (p) => {
                if (p.endsWith('.wasm')) {
                    return path.resolve('./node_modules/@php-wasm/node-8-3/asyncify/8_3_32/php_8_3.wasm');
                }
                return p;
            }
        }
    });

    const php = new PHP(runtime);
    
    // اتصال فایل سیستم میزبان به WASM
    useHostFilesystem(php);

    // ایجاد پوشه‌های سیستم و سشن
    php.mkdirTree('/home/web_user');
    php.mkdirTree('/tmp/sessions');
    const dataDir = path.join(rootDir, 'data');
    if (!fs.existsSync(dataDir)) {
        fs.mkdirSync(dataDir, { recursive: true });
    }
    php.mkdirTree(dataDir);

    // ایجاد نمونه هندلر درخواست‌های PHP
    const phpHandler = new PHPRequestHandler({
        php: php,
        documentRoot: rootDir,
        cookieStore: null
    });

    // چک کردن و راه‌اندازی اولیه دیتابیس در صورت نیاز
    const dbFile = path.join(dataDir, 'family_bank.sqlite');
    if (!fs.existsSync(dbFile) || fs.statSync(dbFile).size === 0) {
        console.log('دیتابیس اولیه یافت نشد؛ در حال نصب خودکار جداول و داده‌های نمونه...');
        try {
            await phpHandler.request({
                url: 'http://localhost:3000/install.php',
                method: 'POST',
                body: { run_install: '1' },
                headers: { 'content-type': 'application/x-www-form-urlencoded' }
            });
            console.log('دیتابیس اولیه با موفقیت نصب و داده‌گذاری شد.');
        } catch (err) {
            console.error('خطا در نصب اولیه دیتابیس:', err);
        }
    }

    // هندلر تمامی درخواست‌های وب
    app.all('*', async (req, res) => {
        try {
            let reqPath = req.path;

            // اگر درخواست فایل استاتیک باشد و فایل وجود داشته باشد
            const ext = path.extname(reqPath);
            if (staticExtensions.includes(ext.toLowerCase())) {
                const fullPath = path.join(rootDir, reqPath);
                if (fs.existsSync(fullPath)) {
                    return res.sendFile(fullPath);
                }
            }

            // مسیریابی پیش‌فرض
            if (reqPath === '/' || reqPath === '') {
                reqPath = '/index.php';
            } else if (!reqPath.endsWith('.php') && !ext) {
                // اگر پسوند ندارد، بررسی وجود فایل php با همین نام
                const phpFilePath = path.join(rootDir, `${reqPath}.php`);
                if (fs.existsSync(phpFilePath)) {
                    reqPath = `${reqPath}.php`;
                }
            }

            const protocol = req.protocol || 'http';
            const host = req.get('host') || `localhost:${PORT}`;
            const fullUrl = `${protocol}://${host}${reqPath}${req.url.includes('?') ? req.url.slice(req.url.indexOf('?')) : ''}`;

            // ارسال درخواست به پی‌اچ‌پی
            const phpResponse = await phpHandler.request({
                url: fullUrl,
                method: req.method,
                headers: req.headers,
                body: req.method !== 'GET' && req.method !== 'HEAD' ? req.body : undefined
            });

            // تنظیم وضعیت پاسخ
            const statusCode = phpResponse.httpStatusCode || (phpResponse.headers?.location ? 302 : 200);
            res.status(statusCode);

            // کپی کردن هدرهای پاسخ پی‌اچ‌پی
            if (phpResponse.headers) {
                for (const [key, val] of Object.entries(phpResponse.headers)) {
                    if (Array.isArray(val)) {
                        val.forEach(v => res.append(key, v));
                    } else if (val) {
                        res.setHeader(key, val);
                    }
                }
            }

            // ارسال بدنه پاسخ
            const responseBody = phpResponse.bytes || phpResponse.text || '';
            res.send(Buffer.from(responseBody));
        } catch (error) {
            console.error('خطا در پردازش درخواست PHP:', error);
            res.status(500).send(`<h3>خطای داخلی سرور</h3><pre>${error.message || error}</pre>`);
        }
    });

    app.listen(PORT, '0.0.0.0', () => {
        console.log(`سرور سیستم بانکداری خانوادگی روی پورت ${PORT} با موفقیت فعال شد.`);
    });
}

bootstrap().catch((err) => {
    console.error('خطای مهلک در راه‌اندازی سرور:', err);
    process.exit(1);
});
