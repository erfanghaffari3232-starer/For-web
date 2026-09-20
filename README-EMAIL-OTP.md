# Erfan AI — Email OTP

ورود با ایمیل و کد ۶ رقمی برای GitHub Pages.

## راه‌اندازی
1. در Supabase یک Project بساز.
2. از Project Settings → API، Project URL و Publishable/Anon Key را بردار.
3. در Authentication → Providers → Email، Email OTP را فعال کن.
4. در `index.html` مقدارهای `YOUR_SUPABASE_URL` و `YOUR_SUPABASE_ANON_KEY` را جایگزین کن.
5. GitHub Pages را روی branch اصلی فعال کن.

**مهم:** Service Role Key یا رمز SMTP را هرگز داخل HTML نگذار.

این پروژه کد تأیید، ورود، خروج و قفل چت تا قبل از تأیید ایمیل را دارد.