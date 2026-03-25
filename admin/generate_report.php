<?php
/**
 * FEATURE 3 — Weekly Safety Report PDF (FPDF)
 * Location: admin/generate_report.php
 *
 * SETUP:
 * 1. Download FPDF from http://www.fpdf.org/
 * 2. Upload the fpdf/ folder to your gasleak/ root
 * 3. Visit: https://ics-dev.io/gasleak/admin/generate_report.php
 *
 * The report includes:
 * - All leak events from the last 7 days
 * - Staff who triggered each event
 * - Response time (leak → reset)
 * - Summary statistics
 */

require_once __DIR__ . '/../core/auth_guard.php';
guard('admin');

// Try to load FPDF
$fpdf_path = __DIR__ . '/../fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    // FPDF not installed — show installation instructions
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Setup Required</title>
    <style>
    body{background:#050d1a;color:#cfe8ff;font-family:Arial;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .box{background:#0b1629;border:1px solid #1a3a5c;border-radius:12px;padding:2.5rem;max-width:550px;text-align:center;}
    h2{color:#00d4ff;margin-bottom:1rem;}
    .steps{text-align:left;margin:1.5rem 0;line-height:2.2;font-size:.9rem;color:#4a7a9b;}
    .steps strong{color:#cfe8ff;}
    code{background:#0d1e30;padding:2px 8px;border-radius:4px;color:#00e5a0;font-size:.85rem;}
    a{color:#00d4ff;}
    .btn{display:inline-block;background:#00d4ff;color:#050d1a;border-radius:6px;padding:.6rem 1.4rem;font-weight:700;text-decoration:none;margin-top:1rem;}
    </style></head>
    <body><div class="box">
    <h2>📦 FPDF Library Required</h2>
    <p>To generate PDF reports, you need to install the FPDF library.</p>
    <div class="steps">
      <strong>Steps to install:</strong><br>
      1. Download FPDF from <a href="http://www.fpdf.org/" target="_blank">fpdf.org</a><br>
      2. Extract the ZIP file<br>
      3. Upload the <code>fpdf/</code> folder to:<br>
      &nbsp;&nbsp;&nbsp;<code>gasleak/fpdf/</code><br>
      4. Come back to this page
    </div>
    <a href="<?= base_url() ?>admin/admin_dashboard.php" class="btn">← Back to Dashboard</a>
    </div></body></html>
    <?php
    exit();
}

require_once $fpdf_path;

$db = db();

// ── Fetch last 7 days leak events ─────────────────────────────
$week_start = date('Y-m-d', strtotime('-7 days'));

$leaks = $db->query("
    SELECT
        l.id,
        u.full_name,
        u.location AS station,
        l.created_at AS leak_time,
        (
            SELECT MIN(l2.created_at)
            FROM user_activity_logs l2
            WHERE l2.user_id = l.user_id
              AND l2.action = 'System Reset'
              AND l2.created_at > l.created_at
              AND l2.created_at < DATE_ADD(l.created_at, INTERVAL 2 HOUR)
        ) AS reset_time
    FROM user_activity_logs l
    JOIN users u ON l.user_id = u.id
    WHERE l.action LIKE '%Leak%'
      AND l.created_at >= '$week_start'
    ORDER BY l.created_at DESC
");

$leak_rows = [];
$total_response = 0;
$responded_count = 0;

while ($row = $leaks->fetch_assoc()) {
    $response_sec = null;
    $response_str = 'N/A';

    if ($row['reset_time']) {
        $response_sec = strtotime($row['reset_time']) - strtotime($row['leak_time']);
        $total_response += $response_sec;
        $responded_count++;

        $mins = floor($response_sec / 60);
        $secs = $response_sec % 60;
        $response_str = $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
    }

    $leak_rows[] = [
        'name'     => $row['full_name'],
        'station'  => $row['station'],
        'time'     => date('M d, Y H:i:s', strtotime($row['leak_time'])),
        'response' => $response_str,
        'resolved' => $row['reset_time'] ? 'Yes' : 'No',
    ];
}

$avg_response = $responded_count > 0
    ? gmdate('i\m s\s', round($total_response / $responded_count))
    : 'N/A';

$total_leaks = count($leak_rows);

// ── Generate PDF ───────────────────────────────────────────────
class GasSimhotPDF extends FPDF {
    public string $report_title = '';
    public string $report_date  = '';

    function Header() {
        // Header background
        $this->SetFillColor(9, 19, 31);
        $this->Rect(0, 0, 210, 30, 'F');

        // Logo text
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetTextColor(0, 212, 255);
        $this->SetY(8);
        $this->Cell(0, 8, 'GAS-SIMHOT', 0, 1, 'C');

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(74, 122, 155);
        $this->Cell(0, 6, 'Gas Safety Monitoring for Homes using IoT', 0, 1, 'C');

        $this->SetDrawColor(22, 51, 80);
        $this->SetLineWidth(0.5);
        $this->Line(10, 30, 200, 30);
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(74, 122, 155);
        $this->Cell(0, 10, 'GAS-SIMHOT Weekly Safety Report  |  Page ' . $this->PageNo() . '  |  Generated: ' . date('M d, Y H:i'), 0, 0, 'C');
    }

    function SectionTitle(string $title) {
        $this->SetFillColor(13, 30, 48);
        $this->SetDrawColor(22, 51, 80);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(0, 212, 255);
        $this->Cell(0, 8, $title, 1, 1, 'L', true);
        $this->Ln(2);
    }

    function StatBox(string $label, string $value, int $r, int $g, int $b) {
        $this->SetFillColor(13, 30, 48);
        $this->SetDrawColor(22, 51, 80);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor($r, $g, $b);
        $this->Cell(45, 12, $value, 1, 0, 'C', true);
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(74, 122, 155);
        $this->SetXY($x - 45, $y + 12);
        $this->Cell(45, 5, $label, 0, 0, 'C');
        $this->SetXY($x, $y);
    }
}

$pdf = new GasSimhotPDF();
$pdf->SetMargins(10, 35, 10);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// ── Report Title ───────────────────────────────────────────────
$pdf->SetFont('Helvetica', 'B', 14);
$pdf->SetTextColor(207, 232, 255);
$pdf->Cell(0, 10, 'WEEKLY SAFETY REPORT', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(74, 122, 155);
$pdf->Cell(0, 6, 'Period: ' . date('M d, Y', strtotime('-7 days')) . ' — ' . date('M d, Y'), 0, 1, 'C');
$pdf->Cell(0, 6, 'Generated by: ' . htmlspecialchars($_SESSION['full_name'] ?? 'Admin'), 0, 1, 'C');
$pdf->Ln(4);

// ── Summary Stats ──────────────────────────────────────────────
$pdf->SectionTitle('SUMMARY STATISTICS');

$pdf->SetX(10);
$pdf->StatBox('TOTAL LEAKS',    (string)$total_leaks,    255, 76, 76);
$pdf->SetX(57);
$pdf->StatBox('RESPONDED',      (string)$responded_count, 0, 229, 160);
$pdf->SetX(104);
$pdf->StatBox('UNRESOLVED',     (string)($total_leaks - $responded_count), 255, 179, 0);
$pdf->SetX(151);
$pdf->StatBox('AVG RESPONSE',   $avg_response,            0, 212, 255);
$pdf->Ln(22);

// ── Leak Events Table ──────────────────────────────────────────
$pdf->SectionTitle('LEAK EVENTS — LAST 7 DAYS');

if (empty($leak_rows)) {
    $pdf->SetFont('Helvetica', 'I', 10);
    $pdf->SetTextColor(74, 122, 155);
    $pdf->Cell(0, 10, 'No leak events recorded in the last 7 days.', 0, 1, 'C');
} else {
    // Table header
    $pdf->SetFillColor(9, 19, 31);
    $pdf->SetDrawColor(22, 51, 80);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(74, 122, 155);

    $pdf->Cell(5,  7, '#',        1, 0, 'C', true);
    $pdf->Cell(45, 7, 'STAFF MEMBER', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'STATION',  1, 0, 'C', true);
    $pdf->Cell(50, 7, 'LEAK TIME', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'RESPONSE', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'RESOLVED', 1, 1, 'C', true);

    // Table rows
    $pdf->SetFont('Helvetica', '', 8);
    $i = 1;
    foreach ($leak_rows as $row) {
        $fill = ($i % 2 === 0);
        $pdf->SetFillColor(13, 30, 48);
        $pdf->SetTextColor(207, 232, 255);

        $pdf->Cell(5,  6, $i,               1, 0, 'C', $fill);
        $pdf->Cell(45, 6, $row['name'],      1, 0, 'L', $fill);
        $pdf->Cell(30, 6, $row['station'],   1, 0, 'C', $fill);
        $pdf->Cell(50, 6, $row['time'],      1, 0, 'C', $fill);

        // Color code response time
        if ($row['response'] !== 'N/A') {
            $pdf->SetTextColor(0, 229, 160);
        } else {
            $pdf->SetTextColor(255, 179, 0);
        }
        $pdf->Cell(25, 6, $row['response'],  1, 0, 'C', $fill);

        if ($row['resolved'] === 'Yes') {
            $pdf->SetTextColor(0, 229, 160);
        } else {
            $pdf->SetTextColor(255, 76, 76);
        }
        $pdf->Cell(25, 6, $row['resolved'],  1, 1, 'C', $fill);

        $pdf->SetTextColor(207, 232, 255);
        $i++;
    }
}

$pdf->Ln(4);

// ── Notes Section ──────────────────────────────────────────────
$pdf->SectionTitle('SAFETY NOTES & RECOMMENDATIONS');
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(74, 122, 155);
$pdf->MultiCell(0, 6,
    "1. All gas leak events must be investigated within 24 hours of occurrence.\n" .
    "2. Staff response time should not exceed 5 minutes from detection.\n" .
    "3. Sensors older than 6 months should be scheduled for maintenance.\n" .
    "4. This report should be reviewed by the Safety Manager weekly.\n" .
    "5. Any unresolved events require immediate follow-up action.", 0, 'L');

// ── Output PDF ─────────────────────────────────────────────────
$filename = 'GAS-SIMHOT_Weekly_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename); // 'D' = force download
exit();