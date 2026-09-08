<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">

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
                  <div style="color:#93c5fd;font-size:13px;">Aviso automático de limpieza de audio</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 28px;">
            <div style="background:#fef2f2;border-left:4px solid #ef4444;border-radius:6px;padding:14px 16px;margin-bottom:20px;">
              <div style="color:#0d1424;font-weight:600;font-size:15px;">{{ $meeting->titulo }}</div>
              <div style="color:#475569;font-size:13px;margin-top:2px;">
                {{ $meeting->fecha_inicio?->translatedFormat('d \d\e F \d\e Y') }}
              </div>
            </div>

            <p style="color:#1f2937;font-size:14px;line-height:1.6;margin:0 0 14px;">
              El audio original de esta reunión pesa <strong>{{ $meeting->audioPesaLegible() }}</strong> en disco
              y se eliminará automáticamente <strong>mañana</strong>, como parte de la limpieza periódica de
              almacenamiento (los audios se conservan 30 días).
            </p>

            <p style="color:#1f2937;font-size:14px;line-height:1.6;margin:0 0 14px;">
              <strong>El acta y la transcripción completa NO se eliminan</strong> — seguirán disponibles
              en Kairo para siempre, sin importar lo que pase con el audio.
            </p>

            <p style="color:#1f2937;font-size:14px;line-height:1.6;margin:0 0 18px;">
              Si necesitas conservar el audio original, descárgalo antes de que se elimine, entrando a
              la reunión en Kairo y abriendo la sección "Audio de la reunión".
            </p>

            <a href="{{ url('/reuniones/'.$meeting->id) }}"
               style="display:inline-block;background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;
                      text-decoration:none;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;">
              Ver reunión en Kairo
            </a>
          </td>
        </tr>

        <tr>
          <td style="padding:18px 28px 26px;border-top:1px solid #e2e8f0;">
            <div style="color:#64748b;font-size:12.5px;">
              Generado automáticamente por <strong style="color:#1d4ed8;">Kairo</strong>.
            </div>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
