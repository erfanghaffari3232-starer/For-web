const OTP_TTL_SECONDS = 10 * 60;
const RATE_LIMIT_SECONDS = 60;

function json_(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

function doGet(e) {
  const p = e && e.parameter ? e.parameter : {};
  const action = String(p.action || "");
  const email = String(p.email || "").trim().toLowerCase();
  const callback = String(p.callback || "");

  let result;
  try {
    if (action === "send") {
      result = sendOtp_(email);
    } else if (action === "verify") {
      result = verifyOtp_(email, String(p.code || "").trim());
    } else {
      result = { ok: false, error: "Invalid action" };
    }
  } catch (err) {
    result = { ok: false, error: "Server error" };
  }

  // JSONP is used because a GitHub Pages site and an Apps Script web app
  // are different origins. No OTP is returned to the browser.
  if (callback && /^[A-Za-z_$][A-Za-z0-9_$]*$/.test(callback)) {
    return ContentService
      .createTextOutput(callback + "(" + JSON.stringify(result) + ");")
      .setMimeType(ContentService.MimeType.JAVASCRIPT);
  }
  return json_(result);
}

function sendOtp_(email) {
  if (!/^\S+@\S+\.\S+$/.test(email)) {
    return { ok: false, error: "Invalid email" };
  }

  const props = PropertiesService.getScriptProperties();
  const key = "otp_" + Utilities.base64EncodeWebSafe(email);
  const last = Number(props.getProperty("sent_" + key) || 0);

  if (Date.now() - last < RATE_LIMIT_SECONDS * 1000) {
    return { ok: false, error: "Please wait before requesting another code." };
  }

  const code = String(Math.floor(100000 + Math.random() * 900000));
  const digest = Utilities.computeDigest(
    Utilities.DigestAlgorithm.SHA_256,
    code,
    Utilities.Charset.UTF_8
  );
  const hash = digest.map(function(b) {
    return (b < 0 ? b + 256 : b).toString(16).padStart(2, "0");
  }).join("");

  CacheService.getScriptCache().put(key, hash, OTP_TTL_SECONDS);
  props.setProperty("sent_" + key, String(Date.now()));

  MailApp.sendEmail({
    to: email,
    subject: "کد ورود Erfan AI",
    htmlBody:
      '<div style="font-family:Arial,sans-serif;direction:rtl">' +
      '<h2>Erfan AI</h2>' +
      '<p>کد تأیید ورود شما:</p>' +
      '<div style="font-size:32px;font-weight:bold;letter-spacing:8px">' +
      code +
      '</div><p>این کد ۱۰ دقیقه اعتبار دارد.</p></div>',
    body: "کد ورود Erfan AI: " + code + "\nاین کد ۱۰ دقیقه اعتبار دارد."
  });

  return { ok: true };
}

function verifyOtp_(email, code) {
  if (!/^\S+@\S+\.\S+$/.test(email) || !/^\d{6}$/.test(code)) {
    return { ok: false, error: "Invalid input" };
  }

  const key = "otp_" + Utilities.base64EncodeWebSafe(email);
  const stored = CacheService.getScriptCache().get(key);

  if (!stored) return { ok: false, error: "Code expired or not found" };

  const digest = Utilities.computeDigest(
    Utilities.DigestAlgorithm.SHA_256,
    code,
    Utilities.Charset.UTF_8
  );
  const hash = digest.map(function(b) {
    return (b < 0 ? b + 256 : b).toString(16).padStart(2, "0");
  }).join("");

  if (hash !== stored) return { ok: false, error: "Wrong code" };

  CacheService.getScriptCache().remove(key);
  return { ok: true };
}