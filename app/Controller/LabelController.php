<?php
namespace App\Controller;

use App\Core\Controller;
use App\Model\Repository\LabelRepository;
use App\Service\QrRenderer;

class LabelController extends Controller
{
    /** @var LabelRepository */
    private $labels;

    public function __construct()
    {
        parent::__construct();
        $this->labels = new LabelRepository();
    }

    /** Übersicht aller Etiketten mit Aktionen. */
    public function index(): void
    {
        $this->render('label/index', [
            'title'  => 'Etiketten',
            'nav'    => 'label',
            'labels' => $this->labels->allLabels(),
        ]);
    }

    /** Einzel-Vorschau eines Etiketts. */
    public function preview($id): void
    {
        $label = $this->labels->find((int) $id);
        if ($label === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        $this->render('label/preview', [
            'title' => 'Etikett ' . $label['code'],
            'nav'   => 'label',
            'label' => $label,
        ]);
    }

    /** Druckbarer Etikettenbogen (alle, optional auf einen Typ gefiltert). */
    public function printSheet(): void
    {
        $all  = $this->labels->allLabels();
        $kind = (string) $this->request->get('kind', '');
        if ($kind !== '') {
            $all = array_values(array_filter($all, function ($l) use ($kind) {
                return $l['kind'] === $kind;
            }));
        }
        $this->render('label/print', [
            'title'  => 'Etiketten drucken',
            'nav'    => 'label',
            'labels' => $all,
        ]);
    }

    /** Serverseitiges QR-PNG (Fallback: clientseitig in den Views). */
    public function qr(): void
    {
        $code = trim((string) $this->request->get('code', ''));
        if ($code === '') {
            http_response_code(400);
            echo 'code fehlt';
            return;
        }
        QrRenderer::output($code);
    }

    /** CSV-Export (UTF-8 + BOM, semikolongetrennt) für den P-touch-Editor. */
    public function export(): void
    {
        $this->requireLogin();
        $labels = $this->labels->allLabels();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="brickbank-etiketten.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM für Excel/P-touch
        fputcsv($out, ['code', 'titel', 'typ', 'standort', 'standort_code'], ';');
        foreach ($labels as $l) {
            fputcsv($out, [
                $l['code'],
                $l['name'],
                $l['kind'],
                $l['parent_name'] ?? '',
                $l['parent_code'] ?? '',
            ], ';');
        }
        fclose($out);
    }
}
