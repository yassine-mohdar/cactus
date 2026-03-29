import { Router } from 'express';

const policyRouter = Router();

function policyHtml(title: string, body: string): string {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>${title} — Nino XO</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #16162A;
      color: rgba(255,255,255,0.85);
      padding: 24px 20px 48px;
      font-size: 15px;
      line-height: 1.65;
      max-width: 720px;
      margin: 0 auto;
    }
    h1 {
      font-size: 22px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 6px;
    }
    .subtitle {
      font-size: 13px;
      color: rgba(255,255,255,0.35);
      margin-bottom: 32px;
    }
    h2 {
      font-size: 15px;
      font-weight: 600;
      color: #9B7FD4;
      margin: 28px 0 8px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
    }
    p { margin-bottom: 12px; color: rgba(255,255,255,0.72); }
    ul { padding-left: 20px; margin-bottom: 12px; color: rgba(255,255,255,0.72); }
    li { margin-bottom: 6px; }
    a { color: #9B7FD4; text-decoration: none; }
    .divider {
      height: 1px;
      background: rgba(255,255,255,0.07);
      margin: 28px 0;
    }
    .footer {
      font-size: 12px;
      color: rgba(255,255,255,0.25);
      margin-top: 40px;
      text-align: center;
    }
  </style>
</head>
<body>
  <h1>${title}</h1>
  <p class="subtitle">Nino XO &middot; NinoWorld &middot; Last updated March 2026</p>
  ${body}
  <div class="footer">Nino XO &middot; Part of NinoWorld &middot; &copy; 2026</div>
</body>
</html>`;
}

const PRIVACY_BODY = `
<h2>1. Information We Collect</h2>
<p>When you use Nino XO, we collect the following types of information:</p>
<ul>
  <li><strong>Account Information</strong> — your name, email address, and profile photo when you sign up or sign in with Google or Apple.</li>
  <li><strong>Gameplay Data</strong> — match history, scores, win/loss records, and in-game tokens.</li>
  <li><strong>Device Information</strong> — device type, operating system version, and app version for diagnostic purposes.</li>
</ul>

<h2>2. How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
  <li>Provide and operate the Nino XO game service.</li>
  <li>Match you with opponents and track your game statistics.</li>
  <li>Send you important updates about your account or the game.</li>
  <li>Detect and prevent fraud, abuse, and cheating.</li>
  <li>Improve our game and develop new features.</li>
</ul>

<h2>3. Data Sharing</h2>
<p>We do not sell your personal information. We may share data with:</p>
<ul>
  <li>Service providers who help us operate the game (e.g. authentication, analytics).</li>
  <li>Law enforcement or regulators when required by law.</li>
</ul>

<div class="divider"></div>

<h2>4. Data Retention</h2>
<p>We retain your account data for as long as your account is active. You may request deletion of your account and associated data at any time by contacting us.</p>

<h2>5. Children's Privacy</h2>
<p>Nino XO is not directed at children under 13. We do not knowingly collect personal information from children under 13. If we become aware that a child under 13 has provided us with personal data, we will delete it promptly.</p>

<h2>6. Security</h2>
<p>We use industry-standard security measures to protect your data, including encrypted connections (HTTPS/TLS) and secure token storage.</p>

<h2>7. Your Rights</h2>
<p>Depending on your location, you may have the right to access, correct, or delete your personal data. To exercise these rights, please contact us at <a href="mailto:support@ninoxo.app">support@ninoxo.app</a>.</p>

<h2>8. Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. We will notify you of significant changes through the app or by email. Continued use of Nino XO after changes constitutes your acceptance of the updated policy.</p>

<h2>9. Contact</h2>
<p>If you have questions or concerns about this Privacy Policy, please contact us at <a href="mailto:support@ninoxo.app">support@ninoxo.app</a>.</p>
`;

const TERMS_BODY = `
<h2>1. Acceptance of Terms</h2>
<p>By downloading, installing, or using Nino XO ("the Game"), you agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use the Game.</p>

<h2>2. Eligibility</h2>
<p>You must be at least 13 years of age to use Nino XO. By using the Game, you represent that you meet this age requirement.</p>

<h2>3. Subscription</h2>
<p>Nino XO is a premium game requiring an active subscription to play. Subscriptions are billed on a recurring basis. You may cancel your subscription at any time through your device's app store settings. No refunds are provided for unused portions of a subscription period.</p>

<h2>4. User Conduct</h2>
<p>You agree not to:</p>
<ul>
  <li>Cheat, exploit bugs, or use unauthorised tools to gain an advantage.</li>
  <li>Harass, abuse, or threaten other players.</li>
  <li>Use the Game for any unlawful purpose.</li>
  <li>Attempt to reverse-engineer, decompile, or hack the Game or its servers.</li>
  <li>Create multiple accounts to circumvent bans or restrictions.</li>
</ul>

<div class="divider"></div>

<h2>5. Virtual Items & Tokens</h2>
<p>In-game tokens and virtual items have no real-world monetary value and cannot be exchanged for cash. We reserve the right to modify or remove virtual items at any time.</p>

<h2>6. Intellectual Property</h2>
<p>All content in Nino XO, including characters, artwork, music, and code, is the property of NinoWorld and protected by copyright law. You may not reproduce, distribute, or create derivative works without our express written permission.</p>

<h2>7. Disclaimers</h2>
<p>Nino XO is provided "as is" without warranties of any kind. We do not guarantee uninterrupted or error-free gameplay. We are not liable for any damages resulting from your use of the Game.</p>

<h2>8. Termination</h2>
<p>We reserve the right to suspend or terminate your account at any time for violations of these Terms or for any other reason at our discretion.</p>

<h2>9. Changes to Terms</h2>
<p>We may update these Terms from time to time. Continued use of the Game after changes constitutes acceptance of the new Terms.</p>

<h2>10. Contact</h2>
<p>For questions about these Terms, contact us at <a href="mailto:support@ninoxo.app">support@ninoxo.app</a>.</p>
`;

policyRouter.get('/policy/privacy', (_req, res) => {
  res.setHeader('Content-Type', 'text/html; charset=utf-8');
  res.send(policyHtml('Privacy Policy', PRIVACY_BODY));
});

policyRouter.get('/policy/terms', (_req, res) => {
  res.setHeader('Content-Type', 'text/html; charset=utf-8');
  res.send(policyHtml('Terms of Use', TERMS_BODY));
});

export default policyRouter;
