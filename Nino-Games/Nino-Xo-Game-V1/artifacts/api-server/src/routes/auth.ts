import { Router } from 'express';
import { createHash, randomBytes, randomInt } from 'crypto';
import { db, playersTable, authProvidersTable, otpTokensTable, appSettingsTable } from '@workspace/db';
import { eq, and, gt, isNull, sql } from 'drizzle-orm';
import { signPlayerJwt } from '../lib/jwt.js';
import { Resend } from 'resend';
import nodemailer from 'nodemailer';
import { jwtVerify, createRemoteJWKSet, type JWTPayload } from 'jose';
import { requireAuth } from '../middleware/auth.js';
import {
  IS_PRODUCTION,
  GMAIL_USER,
  GMAIL_APP_PASSWORD,
  RESEND_API_KEY,
  OTP_FROM_EMAIL,
  GOOGLE_CLIENT_ID,
  APPLE_CLIENT_ID,
} from '../config.js';

const authRouter = Router();

// ── Email sender ──────────────────────────────────────────────────────────────
// Priority: Gmail SMTP → Resend → console log (dev only)

const gmailTransport = GMAIL_USER && GMAIL_APP_PASSWORD
  ? nodemailer.createTransport({
      host: 'smtp.gmail.com',
      port: 587,
      secure: false,
      auth: { user: GMAIL_USER, pass: GMAIL_APP_PASSWORD },
    })
  : null;

const resend = !gmailTransport && RESEND_API_KEY
  ? new Resend(RESEND_API_KEY)
  : null;

if (gmailTransport) {
  console.info(`[auth] Gmail SMTP configured — sending OTP emails via ${GMAIL_USER}`);
} else if (resend) {
  console.info('[auth] Resend configured — sending OTP emails via Resend.');
} else if (IS_PRODUCTION) {
  console.error('[auth] No email provider configured in production. Set GMAIL_USER + GMAIL_APP_PASSWORD (or RESEND_API_KEY). OTP delivery will fail.');
} else {
  console.warn('[auth] No email provider configured — OTP codes will be logged to the console (development only).');
}

// ── Shared email palette ──────────────────────────────────────────────────────

function emailPalette(theme: 'dark' | 'light') {
  const isDark = theme === 'dark';
  return {
    isDark,
    pageBg:        isDark ? '#0d0d1a'                   : '#f0f0f8',
    cardBg:        isDark ? '#16162a'                   : '#ffffff',
    // Solid fallback for email clients that ignore gradients on <td>
    headerBgSolid: isDark ? '#2d1b69'                   : '#e8e0ff',
    // Gradient layered via background-image (modern clients only)
    headerGradient:isDark ? 'linear-gradient(135deg,#2d1b69 0%,#1a1035 100%)'
                          : 'linear-gradient(135deg,#e8e0ff 0%,#d4caff 100%)',
    headerBorder:  isDark ? 'rgba(124,92,252,0.25)'     : 'rgba(124,92,252,0.3)',
    titleColor:    isDark ? '#ffffff'                   : '#1a0060',
    subtitleClr:   isDark ? 'rgba(255,255,255,0.45)'    : 'rgba(60,20,120,0.55)',
    bodyText:      isDark ? 'rgba(255,255,255,0.55)'    : 'rgba(30,10,80,0.65)',
    bodyTextMid:   isDark ? 'rgba(255,255,255,0.75)'    : 'rgba(30,10,80,0.85)',
    codeBg:        isDark ? '#0d0d1a'                   : '#f4f0ff',
    codeBorder:    isDark ? 'rgba(124,92,252,0.5)'      : 'rgba(100,60,220,0.4)',
    rowBorderClr:  isDark ? 'rgba(255,255,255,0.07)'    : 'rgba(100,60,200,0.12)',
    metaLabelClr:  isDark ? 'rgba(255,255,255,0.4)'     : 'rgba(60,20,120,0.5)',
    metaValueClr:  isDark ? 'rgba(255,255,255,0.75)'    : 'rgba(30,10,80,0.8)',
    warnBg:        isDark ? 'rgba(255,100,80,0.1)'      : 'rgba(255,80,60,0.07)',
    warnBorder:    isDark ? 'rgba(255,100,80,0.25)'     : 'rgba(255,80,60,0.2)',
    warnText:      isDark ? 'rgba(255,140,120,0.9)'     : '#c0310a',
    successBg:     isDark ? 'rgba(92,175,122,0.1)'      : 'rgba(60,160,90,0.07)',
    successBorder: isDark ? 'rgba(92,175,122,0.3)'      : 'rgba(60,160,90,0.25)',
    successText:   isDark ? 'rgba(130,220,160,0.9)'     : '#1a6b38',
    footerBgSolid: isDark ? '#0d0d1a'                   : '#f4f0ff',
    footerBorder:  isDark ? 'rgba(255,255,255,0.06)'    : 'rgba(100,60,200,0.1)',
    footerText:    isDark ? 'rgba(255,255,255,0.2)'     : 'rgba(60,20,120,0.35)',
    xSymbolBg:     isDark ? 'rgba(255,215,0,0.15)'      : 'rgba(255,180,0,0.15)',
    xBorder:       isDark ? 'rgba(255,215,0,0.4)'       : 'rgba(220,150,0,0.4)',
    oSymbolBg:     isDark ? 'rgba(124,92,252,0.15)'     : 'rgba(100,60,220,0.12)',
    oBorder:       isDark ? 'rgba(124,92,252,0.4)'      : 'rgba(100,60,220,0.35)',
    oColor:        isDark ? '#9d7dff'                   : '#6040cc',
  };
}

// ── Shared email chrome (header + card wrapper + footer) ──────────────────────
// Strategy for dark/light adaptation:
//   • Inline styles use the admin-configured theme as the fallback
//     (this is what Gmail, Outlook, and other non-adaptive clients always show)
//   • A <style> block with @media (prefers-color-scheme: dark/light) overrides
//     ensures Apple Mail, iOS Mail, and Samsung Mail automatically switch to the
//     correct theme for the recipient's device — independent of the admin setting
//   • CSS class names (nx-*) are added to all key elements so the media query
//     can override inline styles via !important
//   • background-color is always set before background-image so the solid color
//     shows as fallback in any client that ignores gradient backgrounds

function buildEmailChrome(opts: {
  title: string;
  subtitle: string;
  preheader: string;
  bodyHtml: string;
  theme: 'dark' | 'light';
}): string {
  const { title, subtitle, preheader, bodyHtml, theme } = opts;
  const base = emailPalette(theme);       // inline / admin-default
  const alt  = emailPalette(theme === 'dark' ? 'light' : 'dark'); // adaptive override
  const altQuery = theme === 'dark'
    ? 'prefers-color-scheme: light'
    : 'prefers-color-scheme: dark';

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="light dark" />
  <meta name="supported-color-schemes" content="light dark" />
  <title>${title}</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
  <style>
    /* ── Adaptive dark/light: overrides inline styles in supporting clients ── */
    @media (${altQuery}) {
      body, .nx-page            { background-color: ${alt.pageBg} !important; }
      .nx-card                  { background-color: ${alt.cardBg} !important; }
      .nx-header                {
        background-color: ${alt.headerBgSolid} !important;
        background-image: ${alt.headerGradient} !important;
        border-bottom-color: ${alt.headerBorder} !important;
      }
      .nx-title                 { color: ${alt.titleColor} !important; }
      .nx-subtitle              { color: ${alt.subtitleClr} !important; }
      .nx-symbol-x              {
        background-color: ${alt.xSymbolBg} !important;
        border-color: ${alt.xBorder} !important;
      }
      .nx-symbol-o              {
        background-color: ${alt.oSymbolBg} !important;
        border-color: ${alt.oBorder} !important;
        color: ${alt.oColor} !important;
      }
      .nx-body                  { background-color: ${alt.cardBg} !important; }
      .nx-body-text             { color: ${alt.bodyText} !important; }
      .nx-body-text-mid         { color: ${alt.bodyTextMid} !important; }
      .nx-big-title             { color: ${alt.titleColor} !important; }
      .nx-code-box              {
        background-color: ${alt.codeBg} !important;
        border-color: ${alt.codeBorder} !important;
      }
      .nx-meta-border-top       { border-top-color: ${alt.rowBorderClr} !important; }
      .nx-meta-border-bot       { border-bottom-color: ${alt.rowBorderClr} !important; }
      .nx-meta-label            { color: ${alt.metaLabelClr} !important; }
      .nx-meta-value            { color: ${alt.metaValueClr} !important; }
      .nx-warn-box              {
        background-color: ${alt.warnBg} !important;
        border-color: ${alt.warnBorder} !important;
      }
      .nx-warn-text             { color: ${alt.warnText} !important; }
      .nx-success-box           {
        background-color: ${alt.successBg} !important;
        border-color: ${alt.successBorder} !important;
      }
      .nx-success-text          { color: ${alt.successText} !important; }
      .nx-footer                {
        background-color: ${alt.footerBgSolid} !important;
        border-top-color: ${alt.footerBorder} !important;
      }
      .nx-footer-text           { color: ${alt.footerText} !important; }
    }
  </style>
</head>
<body class="nx-page" style="margin:0;padding:0;background-color:${base.pageBg};font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <!-- Preheader: hidden text shown as snippet in inbox list -->
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">${preheader}&nbsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;&hairsp;&zwnj;</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="nx-page" style="background-color:${base.pageBg};min-height:100vh;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" width="100%" class="nx-card" style="max-width:480px;background-color:${base.cardBg};border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.15);">
          <tr>
            <td class="nx-header" bgcolor="${base.headerBgSolid}" style="background-color:${base.headerBgSolid};background-image:${base.headerGradient};padding:32px 40px 28px;text-align:center;border-bottom:1px solid ${base.headerBorder};">
              <div style="margin-bottom:14px;">
                <span class="nx-symbol-x" style="display:inline-block;width:42px;height:42px;line-height:42px;border-radius:10px;background-color:${base.xSymbolBg};border:1.5px solid ${base.xBorder};font-size:22px;font-weight:900;color:#FFD700;text-align:center;vertical-align:middle;margin-right:8px;">✕</span>
                <span class="nx-symbol-o" style="display:inline-block;width:42px;height:42px;line-height:42px;border-radius:10px;background-color:${base.oSymbolBg};border:1.5px solid ${base.oBorder};font-size:22px;font-weight:900;color:${base.oColor};text-align:center;vertical-align:middle;">○</span>
              </div>
              <div class="nx-title" style="font-size:28px;font-weight:900;letter-spacing:2px;color:${base.titleColor};text-transform:uppercase;">NINO XO</div>
              <div class="nx-subtitle" style="font-size:13px;color:${base.subtitleClr};margin-top:4px;letter-spacing:1px;text-transform:uppercase;">${subtitle}</div>
            </td>
          </tr>
          <tr>
            <td class="nx-body" style="padding:36px 40px 32px;text-align:center;background-color:${base.cardBg};">
              ${bodyHtml}
            </td>
          </tr>
          <tr>
            <td class="nx-footer" bgcolor="${base.footerBgSolid}" style="background-color:${base.footerBgSolid};padding:20px 40px;text-align:center;border-top:1px solid ${base.footerBorder};">
              <p class="nx-footer-text" style="margin:0;font-size:11px;color:${base.footerText};letter-spacing:0.5px;">
                © 2025 Nino XO &nbsp;·&nbsp; This is an automated message, do not reply.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
}

// ── OTP email ─────────────────────────────────────────────────────────────────

function buildOtpEmailHtml(code: string, theme: 'dark' | 'light'): string {
  const c = emailPalette(theme);

  const body = `
    <p class="nx-body-text" style="margin:0 0 8px;font-size:15px;color:${c.bodyText};letter-spacing:0.3px;">Your one-time sign-in code</p>
    <div class="nx-code-box" style="margin:20px auto 24px;display:inline-block;background-color:${c.codeBg};border:2px solid ${c.codeBorder};border-radius:16px;padding:20px 40px;">
      <span style="font-size:44px;font-weight:900;letter-spacing:12px;color:#FFD700;font-family:'Courier New',Courier,monospace;">${code}</span>
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
      <tr>
        <td class="nx-meta-border-top nx-meta-border-bot" style="padding:10px 0;border-top:1px solid ${c.rowBorderClr};border-bottom:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">⏱ Expires in</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">10 minutes</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="nx-meta-border-bot" style="padding:10px 0;border-bottom:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🔒 Single use</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Code is used once</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <div class="nx-warn-box" style="background-color:${c.warnBg};border:1px solid ${c.warnBorder};border-radius:10px;padding:12px 16px;">
      <p class="nx-warn-text" style="margin:0;font-size:12px;color:${c.warnText};line-height:1.5;">
        🛡️ <strong>Never share this code.</strong> Nino XO staff will never ask for your sign-in code.
        If you didn't request this, you can safely ignore this email.
      </p>
    </div>`;

  return buildEmailChrome({
    title: 'Your Nino XO sign-in code',
    subtitle: 'Sign-in verification',
    preheader: `Your Nino XO sign-in code is ${code}. It expires in 10 minutes.`,
    bodyHtml: body,
    theme,
  });
}

// ── Welcome email ─────────────────────────────────────────────────────────────

function buildWelcomeEmailHtml(playerName: string, theme: 'dark' | 'light'): string {
  const c = emailPalette(theme);

  const body = `
    <p class="nx-big-title" style="margin:0 0 20px;font-size:22px;font-weight:900;color:${c.titleColor};letter-spacing:0.5px;">Welcome, ${playerName}! 🎉</p>
    <p class="nx-body-text" style="margin:0 0 16px;font-size:15px;color:${c.bodyText};line-height:1.6;">
      You're now part of <strong class="nx-body-text-mid" style="color:${c.bodyTextMid};">Nino XO</strong> — a premium 1v1 Tic-Tac-Toe game set in the NinoWorld cactus universe.
    </p>
    <p class="nx-body-text" style="margin:0 0 28px;font-size:15px;color:${c.bodyText};line-height:1.6;">
      Challenge friends, climb the leaderboard, complete missions, and collect unique cactus characters. Every match counts.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
      <tr>
        <td class="nx-meta-border-top" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🎮 Game modes</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Quick, Bot, Friend, Online</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="nx-meta-border-top" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🌵 Characters</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Collect them all</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="nx-meta-border-top nx-meta-border-bot" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};border-bottom:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🏆 Leaderboard</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Compete globally</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <div class="nx-success-box" style="background-color:${c.successBg};border:1px solid ${c.successBorder};border-radius:10px;padding:14px 16px;">
      <p class="nx-success-text" style="margin:0;font-size:13px;color:${c.successText};line-height:1.5;">
        ✅ <strong>You're all set.</strong> Open the Nino XO app to play your first match and start your journey.
      </p>
    </div>`;

  return buildEmailChrome({
    title: 'Welcome to Nino XO',
    subtitle: 'Welcome to the arena',
    preheader: `Welcome to Nino XO, ${playerName}! Your account is ready — open the app to play your first match.`,
    bodyHtml: body,
    theme,
  });
}

// ── Farewell (account deleted) email ─────────────────────────────────────────

function buildFarewellEmailHtml(playerName: string, theme: 'dark' | 'light'): string {
  const c = emailPalette(theme);

  const body = `
    <p class="nx-big-title" style="margin:0 0 20px;font-size:22px;font-weight:900;color:${c.titleColor};letter-spacing:0.5px;">Goodbye, ${playerName}</p>
    <p class="nx-body-text" style="margin:0 0 16px;font-size:15px;color:${c.bodyText};line-height:1.6;">
      Your Nino XO account has been <strong class="nx-body-text-mid" style="color:${c.bodyTextMid};">permanently deleted</strong>. All your data — matches, tokens, progress, and profile — has been removed from our systems.
    </p>
    <p class="nx-body-text" style="margin:0 0 28px;font-size:15px;color:${c.bodyText};line-height:1.6;">
      Thank you for the games, the wins, and the moments you spent in the arena. It was a pleasure having you in the NinoWorld universe.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
      <tr>
        <td class="nx-meta-border-top" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🗑️ Account data</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Permanently deleted</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="nx-meta-border-top" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">🏆 Match history</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Permanently deleted</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="nx-meta-border-top nx-meta-border-bot" style="padding:12px 0;border-top:1px solid ${c.rowBorderClr};border-bottom:1px solid ${c.rowBorderClr};">
          <table role="presentation" width="100%">
            <tr>
              <td class="nx-meta-label" style="font-size:13px;color:${c.metaLabelClr};text-align:left;padding-left:4px;">💰 Tokens &amp; progress</td>
              <td class="nx-meta-value" style="font-size:13px;font-weight:700;color:${c.metaValueClr};text-align:right;padding-right:4px;">Permanently deleted</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <div class="nx-warn-box" style="background-color:${c.warnBg};border:1px solid ${c.warnBorder};border-radius:10px;padding:14px 16px;">
      <p class="nx-warn-text" style="margin:0;font-size:13px;color:${c.warnText};line-height:1.5;">
        If you ever want to come back, you're always welcome. A new account is just a sign-in away.
      </p>
    </div>`;

  return buildEmailChrome({
    title: 'Your Nino XO account has been deleted',
    subtitle: 'Account deleted',
    preheader: `Your Nino XO account has been permanently deleted. All data has been removed. Thank you for playing.`,
    bodyHtml: body,
    theme,
  });
}

// ── Email theme helper ────────────────────────────────────────────────────────

async function getEmailTheme(): Promise<'dark' | 'light'> {
  try {
    const [row] = await db
      .select({ value: appSettingsTable.value })
      .from(appSettingsTable)
      .where(eq(appSettingsTable.key, 'email_theme'))
      .limit(1);
    return (row?.value === 'light') ? 'light' : 'dark';
  } catch {
    return 'dark';
  }
}

// ── Generic email sender ──────────────────────────────────────────────────────

async function sendEmail(to: string, subject: string, text: string, html: string): Promise<void> {
  if (gmailTransport) {
    await gmailTransport.sendMail({ from: OTP_FROM_EMAIL, to, subject, text, html });
    return;
  }
  if (resend) {
    const { error } = await resend.emails.send({ from: OTP_FROM_EMAIL, to, subject, text, html });
    if (error) throw new Error(`Email delivery failed: ${error.message}`);
    return;
  }
  if (IS_PRODUCTION) {
    throw new Error('Email delivery is not configured. Set GMAIL_USER + GMAIL_APP_PASSWORD.');
  }
  console.warn(`[auth] No email provider — would have sent "${subject}" to ${to}`);
}

// ── OTP email builder + sender ────────────────────────────────────────────────

async function buildOtpEmail(code: string): Promise<{ subject: string; text: string; html: string }> {
  const subject = 'Your Nino XO sign-in code';
  const text = [
    'NINO XO — Sign-in code',
    '',
    `Your verification code is: ${code}`,
    '',
    'Enter this code in the app to sign in.',
    'It expires in 10 minutes.',
    '',
    'If you did not request this code, you can safely ignore this email.',
    'Never share your sign-in code with anyone.',
    '',
    '— The Nino XO Team',
  ].join('\n');
  const theme = await getEmailTheme();
  const html = buildOtpEmailHtml(code, theme);
  return { subject, text, html };
}

async function sendOtpEmail(email: string, code: string): Promise<void> {
  const { subject, text, html } = await buildOtpEmail(code);
  if (!gmailTransport && !resend && !IS_PRODUCTION) {
    // Dev-only: log the code so local auth flows still work without an email provider
    console.warn(`[auth] No email provider — OTP code for ${email}: ${code}`);
    return;
  }
  await sendEmail(email, subject, text, html);
}

// ── Welcome email sender ──────────────────────────────────────────────────────

async function sendWelcomeEmail(email: string, playerName: string): Promise<void> {
  try {
    const theme = await getEmailTheme();
    const subject = 'Welcome to Nino XO 🌵';
    const text = [
      `Welcome to Nino XO, ${playerName}!`,
      '',
      "You're now part of a premium 1v1 Tic-Tac-Toe game set in the NinoWorld cactus universe.",
      '',
      'Open the app to play your first match, collect characters, and climb the leaderboard.',
      '',
      'Game modes: Quick Match, vs Bot, vs Friend, Online',
      '',
      '— The Nino XO Team',
    ].join('\n');
    const html = buildWelcomeEmailHtml(playerName, theme);
    await sendEmail(email, subject, text, html);
  } catch (err) {
    console.error('[auth] Failed to send welcome email:', err);
  }
}

// ── Farewell email sender ─────────────────────────────────────────────────────

async function sendFarewellEmail(email: string, playerName: string): Promise<void> {
  try {
    const theme = await getEmailTheme();
    const subject = 'Your Nino XO account has been deleted';
    const text = [
      `Goodbye, ${playerName}.`,
      '',
      'Your Nino XO account has been permanently deleted.',
      'All your data — matches, tokens, progress, and profile — has been removed.',
      '',
      'Thank you for the games and the time you spent in the arena.',
      'If you ever want to come back, a new account is just a sign-in away.',
      '',
      '— The Nino XO Team',
    ].join('\n');
    const html = buildFarewellEmailHtml(playerName, theme);
    await sendEmail(email, subject, text, html);
  } catch (err) {
    console.error('[auth] Failed to send farewell email:', err);
  }
}

// ── OTP rate-limit (in-memory, simple per-email window) ───────────────────────

const otpRateMap = new Map<string, { count: number; windowStart: number }>();
const OTP_RATE_LIMIT = 3;
const OTP_RATE_WINDOW_MS = 15 * 60 * 1000;

function checkOtpRateLimit(email: string): boolean {
  const now = Date.now();
  const entry = otpRateMap.get(email);
  if (!entry || now - entry.windowStart > OTP_RATE_WINDOW_MS) {
    otpRateMap.set(email, { count: 1, windowStart: now });
    return true;
  }
  if (entry.count >= OTP_RATE_LIMIT) return false;
  entry.count++;
  return true;
}

// ── OTP verify rate-limit (per email, sliding window) ────────────────────────
// Limits failed verification attempts to prevent brute-force of 6-digit codes.
// Successful verifications clear the counter so normal users are never locked out.

const otpVerifyRateMap = new Map<string, { failures: number; windowStart: number }>();
const OTP_VERIFY_LIMIT = 5;
const OTP_VERIFY_WINDOW_MS = 15 * 60 * 1000;

function checkOtpVerifyRateLimit(email: string): boolean {
  const now = Date.now();
  const entry = otpVerifyRateMap.get(email);
  if (!entry || now - entry.windowStart > OTP_VERIFY_WINDOW_MS) return true;
  return entry.failures < OTP_VERIFY_LIMIT;
}

function recordOtpVerifyFailure(email: string): void {
  const now = Date.now();
  const entry = otpVerifyRateMap.get(email);
  if (!entry || now - entry.windowStart > OTP_VERIFY_WINDOW_MS) {
    otpVerifyRateMap.set(email, { failures: 1, windowStart: now });
  } else {
    entry.failures++;
  }
}

function clearOtpVerifyRateLimit(email: string): void {
  otpVerifyRateMap.delete(email);
}

// ── OAuth audience configuration ─────────────────────────────────────────────
// GOOGLE_CLIENT_ID and APPLE_CLIENT_ID are imported from config.ts above.
// Audience (aud) claim validation prevents tokens issued for other apps from
// being accepted by this backend. Required in production; optional in development.

if (!GOOGLE_CLIENT_ID) {
  IS_PRODUCTION
    ? console.error('[auth] GOOGLE_CLIENT_ID is required in production for Google token audience validation.')
    : console.warn('[auth] GOOGLE_CLIENT_ID not set — Google token audience is not validated (development only).');
}
if (!APPLE_CLIENT_ID) {
  IS_PRODUCTION
    ? console.error('[auth] APPLE_CLIENT_ID is required in production for Apple token audience validation.')
    : console.warn('[auth] APPLE_CLIENT_ID not set — Apple token audience is not validated (development only).');
}

// ── Helpers ────────────────────────────────────────────────────────────────────

function hashCode(code: string): string {
  return createHash('sha256').update(code).digest('hex');
}

function generatePlayerId(): string {
  return `${Date.now()}_${randomBytes(8).toString('hex')}`;
}

function generateOtpId(): string {
  return `otp_${randomBytes(12).toString('hex')}`;
}

function generateAuthProviderId(): string {
  return `ap_${randomBytes(12).toString('hex')}`;
}

/**
 * Look up a player by provider (google/apple/email) + providerId.
 * Returns null if no link exists.
 */
async function findPlayerByProvider(
  provider: 'google' | 'apple' | 'email',
  providerId: string,
): Promise<{ id: string; isAdmin: boolean } | null> {
  // For email providers the provider_id is an email address; use case-insensitive
  // comparison to handle legacy rows stored with mixed-case. Google/Apple provider
  // IDs are opaque sub values and remain exact-match.
  const providerIdCondition = provider === 'email'
    ? sql`lower(${authProvidersTable.providerId}) = ${providerId}`
    : eq(authProvidersTable.providerId, providerId);

  const [row] = await db
    .select({ playerId: authProvidersTable.playerId })
    .from(authProvidersTable)
    .where(
      and(
        eq(authProvidersTable.provider, provider),
        providerIdCondition,
      ),
    )
    .limit(1);

  if (!row) return null;

  const [player] = await db
    .select({ id: playersTable.id, isAdmin: playersTable.isAdmin })
    .from(playersTable)
    .where(eq(playersTable.id, row.playerId))
    .limit(1);

  return player ?? null;
}

/**
 * Look up or create a player by verified email address, then link a provider.
 * Only call this with an email that was verified by the auth provider (not client-supplied).
 */
async function upsertPlayerByVerifiedEmail(
  verifiedEmail: string,
  provider: 'google' | 'apple' | 'email',
  providerId: string,
  displayName?: string,
): Promise<{ id: string; isAdmin: boolean }> {
  // Check if an existing player has this email (case-insensitive).
  // verifiedEmail is already lowercased by callers; lower() in SQL catches
  // legacy rows that may have been stored with mixed-case.
  const [existingPlayer] = await db
    .select({ id: playersTable.id, isAdmin: playersTable.isAdmin })
    .from(playersTable)
    .where(sql`lower(${playersTable.email}) = ${verifiedEmail}`)
    .limit(1);

  if (existingPlayer) {
    // Link this provider to the existing player
    await db
      .insert(authProvidersTable)
      .values({
        id: generateAuthProviderId(),
        playerId: existingPlayer.id,
        provider,
        providerId,
      })
      .onConflictDoNothing();
    return existingPlayer;
  }

  // Create a new player
  const newId = generatePlayerId();
  const name = await uniqueName(
    displayName ?? verifiedEmail.split('@')[0] ?? 'Player',
  );

  await db.insert(playersTable).values({
    id: newId,
    name,
    email: verifiedEmail,
    isAdmin: false,
  });

  await db.insert(authProvidersTable).values({
    id: generateAuthProviderId(),
    playerId: newId,
    provider,
    providerId,
  });

  // Fire welcome email asynchronously — never block login on email delivery
  sendWelcomeEmail(verifiedEmail, name).catch(() => {});

  return { id: newId, isAdmin: false };
}

async function uniqueName(base: string): Promise<string> {
  const sanitized = base.replace(/[^a-zA-Z0-9_\- ]/g, '').slice(0, 24) || 'Player';
  const [existing] = await db
    .select({ name: playersTable.name })
    .from(playersTable)
    .where(eq(playersTable.name, sanitized))
    .limit(1);
  if (!existing) return sanitized;
  return `${sanitized.slice(0, 18)}_${randomBytes(3).toString('hex')}`;
}

async function buildAuthResponse(playerId: string, isAdmin: boolean) {
  const [profile] = await db
    .select()
    .from(playersTable)
    .where(eq(playersTable.id, playerId))
    .limit(1);

  if (!profile) {
    return null;
  }

  // Enforce block at token issuance — blocked users never receive a JWT
  if (profile.isBlocked) {
    return { blocked: true } as const;
  }

  // Use current DB isAdmin (not the caller-supplied value) so demotion takes
  // effect on the very next login even if the old JWT is still live.
  const jwt = await signPlayerJwt(playerId, profile.isAdmin);
  return { ok: true, token: jwt, player: profile };
}

// ── POST /auth/otp/request ────────────────────────────────────────────────────

authRouter.post('/auth/otp/request', async (req, res) => {
  const { email } = req.body as { email?: string };
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    res.status(400).json({ error: 'Valid email address is required' });
    return;
  }
  const normalized = email.toLowerCase().trim();

  if (!checkOtpRateLimit(normalized)) {
    res.status(429).json({ error: 'Too many OTP requests. Please wait 15 minutes.' });
    return;
  }

  // Use crypto.randomInt for cryptographically secure OTP generation
  const code = randomInt(0, 1_000_000).toString().padStart(6, '0');
  const codeHash = hashCode(code);
  const expiresAt = new Date(Date.now() + 10 * 60 * 1000);

  try {
    // Invalidate any existing unused tokens for this email so that only the
    // freshest code is valid. This prevents confusion if the user receives
    // an old email and tries to enter a stale code.
    await db
      .update(otpTokensTable)
      .set({ usedAt: new Date() })
      .where(
        and(
          eq(otpTokensTable.email, normalized),
          isNull(otpTokensTable.usedAt),
        ),
      );

    // Token is written to DB before the email is sent. If delivery fails, the token
    // remains unused and valid until expiry; the client can retry by requesting a
    // fresh code. Writing first avoids lost tokens on partial failures.
    await db.insert(otpTokensTable).values({
      id: generateOtpId(),
      email: normalized,
      codeHash,
      expiresAt,
    });

    await sendOtpEmail(normalized, code);
    res.json({ ok: true, message: 'Verification code sent to your email' });
  } catch {
    res.status(500).json({ error: 'Failed to send verification code' });
  }
});

// ── POST /auth/otp/verify ─────────────────────────────────────────────────────

authRouter.post('/auth/otp/verify', async (req, res) => {
  const { email, code } = req.body as { email?: string; code?: string };
  if (!email || !code) {
    res.status(400).json({ error: 'email and code are required' });
    return;
  }
  const normalized = email.toLowerCase().trim();

  // Check verify rate limit before doing any DB work
  if (!checkOtpVerifyRateLimit(normalized)) {
    res.status(429).json({ error: 'Too many failed verification attempts. Please request a new code.' });
    return;
  }

  const codeHash = hashCode(code.trim());
  const now = new Date();

  try {
    // Atomic single-use consumption: the UPDATE only succeeds if the token is still
    // valid and unused. The RETURNING clause tells us whether we won the race.
    // Concurrent requests for the same token will get zero rows back and be rejected.
    const consumed = await db
      .update(otpTokensTable)
      .set({ usedAt: now })
      .where(
        and(
          eq(otpTokensTable.email, normalized),
          eq(otpTokensTable.codeHash, codeHash),
          gt(otpTokensTable.expiresAt, now),
          isNull(otpTokensTable.usedAt),
        ),
      )
      .returning({ id: otpTokensTable.id });

    if (consumed.length === 0) {
      // Record the failed attempt; after OTP_VERIFY_LIMIT failures the account is
      // temporarily locked until the window expires or a fresh OTP is verified.
      recordOtpVerifyFailure(normalized);
      res.status(401).json({ error: 'Invalid or expired verification code' });
      return;
    }

    // Success: clear the failure counter so the user is never locked out of
    // a valid subsequent session.
    clearOtpVerifyRateLimit(normalized);
    const player = await upsertPlayerByVerifiedEmail(normalized, 'email', normalized);
    const authResult = await buildAuthResponse(player.id, player.isAdmin);
    if (!authResult) { res.status(500).json({ error: 'Verification failed' }); return; }
    if ('blocked' in authResult) { res.status(403).json({ error: 'Your account has been suspended. Please contact support.' }); return; }
    res.json(authResult);
  } catch {
    res.status(500).json({ error: 'Verification failed' });
  }
});

// ── POST /auth/google ─────────────────────────────────────────────────────────

const GOOGLE_JWKS_URL = new URL('https://www.googleapis.com/oauth2/v3/certs');
const googleJwks = createRemoteJWKSet(GOOGLE_JWKS_URL);

authRouter.post('/auth/google', async (req, res) => {
  // In production, require GOOGLE_CLIENT_ID so audience is always validated
  if (IS_PRODUCTION && !GOOGLE_CLIENT_ID) {
    res.status(503).json({ error: 'Google sign-in is not configured on this server' });
    return;
  }

  const { idToken } = req.body as { idToken?: string };
  if (!idToken) {
    res.status(400).json({ error: 'idToken is required' });
    return;
  }
  try {
    const jwtOptions: Parameters<typeof jwtVerify>[2] = {
      issuer: ['accounts.google.com', 'https://accounts.google.com'],
    };
    // Validate audience against configured client ID (required in production)
    if (GOOGLE_CLIENT_ID) {
      jwtOptions.audience = GOOGLE_CLIENT_ID;
    }

    const { payload } = await jwtVerify(idToken, googleJwks, jwtOptions) as {
      payload: JWTPayload & {
        sub?: string;
        email?: string;
        name?: string;
        email_verified?: boolean;
      };
    };

    if (!payload.sub) {
      res.status(400).json({ error: 'Invalid Google token — missing sub' });
      return;
    }

    // Check for existing provider link first (fast path for returning users)
    const existing = await findPlayerByProvider('google', payload.sub);
    if (existing) {
      const authResult = await buildAuthResponse(existing.id, existing.isAdmin);
      if (!authResult) { res.status(500).json({ error: 'Authentication failed' }); return; }
      if ('blocked' in authResult) { res.status(403).json({ error: 'Your account has been suspended. Please contact support.' }); return; }
      res.json(authResult);
      return;
    }

    // New Google user — email must be present and verified in the token
    if (!payload.email) {
      res.status(400).json({ error: 'Google token did not include an email address' });
      return;
    }
    if (payload.email_verified !== true) {
      res.status(400).json({ error: 'Google account email is not verified' });
      return;
    }

    const player = await upsertPlayerByVerifiedEmail(
      payload.email.toLowerCase(),
      'google',
      payload.sub,
      payload.name,
    );
    const authResult = await buildAuthResponse(player.id, player.isAdmin);
    if (!authResult) { res.status(500).json({ error: 'Authentication failed' }); return; }
    if ('blocked' in authResult) { res.status(403).json({ error: 'Your account has been suspended. Please contact support.' }); return; }
    res.json(authResult);
  } catch (err) {
    const msg = err instanceof Error ? err.message : 'Unknown error';
    res.status(401).json({ error: `Google authentication failed: ${msg}` });
  }
});

// ── POST /auth/apple ──────────────────────────────────────────────────────────
//
// Apple only provides the email claim on the FIRST sign-in. On subsequent
// sign-ins the token contains only the stable `sub` (user identifier).
// Security contract:
//   - Never trust email from the request body — it is not verified by Apple.
//   - On first sign-in: use verified email from token claims to create/link.
//   - On subsequent sign-ins: look up existing (provider='apple', sub) link.
//   - If sub is unknown AND token has no email: reject (cannot verify identity).

const APPLE_JWKS_URL = new URL('https://appleid.apple.com/auth/keys');
const appleJwks = createRemoteJWKSet(APPLE_JWKS_URL);

authRouter.post('/auth/apple', async (req, res) => {
  // In production, require APPLE_CLIENT_ID so audience is always validated
  if (IS_PRODUCTION && !APPLE_CLIENT_ID) {
    res.status(503).json({ error: 'Apple sign-in is not configured on this server' });
    return;
  }

  const { identityToken, fullName } = req.body as {
    identityToken?: string;
    fullName?: string;
  };

  if (!identityToken) {
    res.status(400).json({ error: 'identityToken is required' });
    return;
  }

  try {
    const jwtOptions: Parameters<typeof jwtVerify>[2] = {
      issuer: 'https://appleid.apple.com',
    };
    // Validate audience against configured client ID (required in production)
    if (APPLE_CLIENT_ID) {
      jwtOptions.audience = APPLE_CLIENT_ID;
    }

    const { payload } = await jwtVerify(identityToken, appleJwks, jwtOptions) as {
      payload: JWTPayload & { sub?: string; email?: string };
    };

    if (!payload.sub) {
      res.status(400).json({ error: 'Invalid Apple token — missing sub' });
      return;
    }

    // 1. Check for existing provider link (returning user — most common path)
    const existing = await findPlayerByProvider('apple', payload.sub);
    if (existing) {
      const authResult = await buildAuthResponse(existing.id, existing.isAdmin);
      if (!authResult) { res.status(500).json({ error: 'Authentication failed' }); return; }
      if ('blocked' in authResult) { res.status(403).json({ error: 'Your account has been suspended. Please contact support.' }); return; }
      res.json(authResult);
      return;
    }

    // 2. New Apple user: email is ONLY available from the token on first sign-in.
    //    Never fall back to request body — it is unverified.
    if (!payload.email) {
      res.status(400).json({
        error:
          'Apple did not provide an email address. ' +
          'This can happen if you previously denied email sharing with this app. ' +
          'Please sign out of the app in your Apple ID settings and try again.',
      });
      return;
    }

    const verifiedEmail = payload.email.toLowerCase();
    const player = await upsertPlayerByVerifiedEmail(
      verifiedEmail,
      'apple',
      payload.sub,
      fullName,
    );
    const authResult = await buildAuthResponse(player.id, player.isAdmin);
    if (!authResult) { res.status(500).json({ error: 'Authentication failed' }); return; }
    if ('blocked' in authResult) { res.status(403).json({ error: 'Your account has been suspended. Please contact support.' }); return; }
    res.json(authResult);
  } catch (err) {
    const msg = err instanceof Error ? err.message : 'Unknown error';
    res.status(401).json({ error: `Apple authentication failed: ${msg}` });
  }
});

// ── GET /auth/me ───────────────────────────────────────────────────────────────
// Returns the authenticated player's full profile. Used by the mobile app to
// re-hydrate session state after an app restart when a valid JWT is stored but
// no in-memory profile exists.

authRouter.get('/auth/me', requireAuth, async (req, res) => {
  const playerId = req.player!.id;
  const isAdmin = req.player!.isAdmin;

  const [profile] = await db
    .select()
    .from(playersTable)
    .where(eq(playersTable.id, playerId))
    .limit(1);

  if (!profile) {
    res.status(404).json({ error: 'Player not found' });
    return;
  }

  res.json({ player: profile, isAdmin });
});

// ── POST /auth/logout ─────────────────────────────────────────────────────────

authRouter.post('/auth/logout', (_req, res) => {
  // JWT is stateless; client is responsible for deleting the stored token.
  // This endpoint provides a consistent logout hook for future server-side
  // token revocation (e.g. revocation list, session table).
  res.json({ ok: true });
});

// ── DELETE /auth/account ───────────────────────────────────────────────────────
// Permanently deletes the authenticated player's account and all associated data.
// Required for Google Play and Apple App Store compliance.

authRouter.delete('/auth/account', requireAuth, async (req, res) => {
  const playerId = req.player!.id;
  try {
    // Fetch the player's email and name before deletion
    // (otp_tokens is keyed by email, not player_id; name is used in farewell email)
    const playerRow = await db.execute(sql`SELECT email, name FROM players WHERE id = ${playerId}`);
    const row = playerRow.rows[0] as { email: string; name: string } | undefined;
    const playerEmail = row?.email ?? null;
    const playerName  = row?.name  ?? 'Player';

    await db.transaction(async (tx) => {
      // challenge_matches refs challenge_match_tokens(token_id) and players(winner_id/loser_id)
      await tx.execute(sql`
        DELETE FROM challenge_matches
          WHERE winner_id = ${playerId}
             OR loser_id  = ${playerId}
             OR match_token_id IN (
                 SELECT token_id FROM challenge_match_tokens WHERE player_id = ${playerId}
               )
      `);
      await tx.execute(sql`DELETE FROM challenge_match_tokens WHERE player_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM challenge_reward_log   WHERE player_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM challenge_participants WHERE player_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM follows WHERE follower_id = ${playerId} OR followee_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM token_transactions WHERE player_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM matches WHERE player_id = ${playerId}`);
      if (playerEmail) {
        await tx.execute(sql`DELETE FROM otp_tokens WHERE email = ${playerEmail}`);
      }
      await tx.execute(sql`DELETE FROM auth_providers WHERE player_id = ${playerId}`);
      await tx.execute(sql`DELETE FROM players WHERE id = ${playerId}`);
    });

    // Fire farewell email after successful deletion — async, never block the response
    if (playerEmail) {
      sendFarewellEmail(playerEmail, playerName).catch(() => {});
    }

    res.json({ ok: true });
  } catch (err) {
    console.error('[auth] delete account error:', err);
    res.status(500).json({ error: 'Failed to delete account' });
  }
});

export default authRouter;
