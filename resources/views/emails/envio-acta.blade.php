<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">
<style>
  .prose-acta h1 { font-size:1.35rem; font-weight:800; color:#0f172a; margin:0 0 .5rem; }
  .prose-acta h2 { font-size:1rem; font-weight:700; color:#1d4ed8; margin:1.25rem 0 .5rem; border-bottom:1px solid #e2e8f0; padding-bottom:.3rem; }
  .prose-acta ul { list-style:disc; padding-left:1.4rem; margin-bottom:.75rem; }
  .prose-acta li { margin-bottom:.25rem; color:#374151; font-size:14px; }
  .prose-acta table { width:100%; font-size:13px; margin:.75rem 0; border-collapse:collapse; }
  .prose-acta th, .prose-acta td { border:1px solid #e2e8f0; padding:.4rem .6rem; text-align:left; }
  .prose-acta th { background:#f8fafc; color:#475569; font-weight:600; }
  .prose-acta p { margin-bottom:.75rem; color:#374151; font-size:14px; line-height:1.6; }
  .prose-acta strong { color:#0f172a; }
</style>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">

        <tr>
          <td style="background:linear-gradient(135deg,#0d1424,#1d3a6b);padding:24px 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="48" style="vertical-align:middle;">
                  <img src="https://app.koqoi.com/kairo.png" width="40" height="40" alt="Kairo"
                       style="border-radius:50%;border:2px solid #60a5fa;display:block;">
                </td>
                <td style="vertical-align:middle;padding-left:12px;">
                  <div style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:.3px;">KAIRO</div>
                  <div style="color:#93c5fd;font-size:13px;">Acta de reunión</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 28px;">
            <div style="background:#eff6ff;border-left:4px solid #3b82f6;border-radius:6px;padding:14px 16px;margin-bottom:20px;">
              <div style="color:#0d1424;font-weight:600;font-size:15px;">{{ $meeting->titulo }}</div>
              <div style="color:#475569;font-size:13px;margin-top:2px;">
                {{ $meeting->fecha_inicio?->translatedFormat('d \d\e F \d\e Y, h:i A') }}
                @if($meeting->duracion_legible) &middot; {{ $meeting->duracion_legible }} @endif
              </div>
            </div>

            <div class="prose-acta">{!! $actaHtml !!}</div>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
            <div style="color:#9ca3af;font-size:12px;text-align:center;">
              Generado automáticamente por KairoMeet &middot; <a href="https://app.koqoi.com/reuniones" style="color:#3b82f6;text-decoration:none;">Ver en Kairo</a>
            </div>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
