<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\PdfGenerator;
use app\services\ReportService;
use app\services\FiscalYearService;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;

/**
 * ReportController renders the reports dashboard and generates downloadable
 * PDF financial summaries.
 *
 * Report types: summary, category (expense breakdown), income-expense
 * (period trend), and budget (current budget status). Each supports the
 * month / fiscal-year / custom-range / lifetime periods.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ReportController extends Controller
{
    /** @var string[] Supported report types */
    private const REPORTS = ['summary', 'category', 'income-expense', 'budget'];

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /**
     * Reports dashboard: choose a report type and period, then download.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $fiscalYears = (new FiscalYearService())->getAvailableFiscalYears();

        return $this->render('index', [
            'reports' => self::reportOptions(),
            'periods' => self::periodOptions(),
            'fiscalYears' => $fiscalYears,
        ]);
    }

    /**
     * Generates and streams a PDF report.
     *
     * Query params: report, period, plus period-specific (year, month, fy,
     * start, end).
     *
     * @return string|\yii\web\Response
     * @throws BadRequestHttpException
     */
    public function actionPdf()
    {
        $request = Yii::$app->request;
        $report = (string) $request->get('report', 'summary');
        $periodType = (string) $request->get('period', ReportService::PERIOD_MONTH);

        if (!in_array($report, self::REPORTS, true)) {
            throw new BadRequestHttpException(Yii::t('app', 'Unknown report type.'));
        }

        // ReportService scopes by the active workspace
        $userId = Yii::$app->workspace->getId();
        $service = new ReportService();

        $period = $service->resolvePeriod($periodType, [
            'year' => $request->get('year'),
            'month' => $request->get('month'),
            'fy' => $request->get('fy'),
            'start' => $request->get('start'),
            'end' => $request->get('end'),
        ], $userId);

        $data = $this->buildReportData($report, $service, $userId, $period);

        $html = $this->renderPartial('pdf/' . $report, array_merge($data, [
            'period' => $period,
            'meta' => $this->meta(),
        ]));

        $filename = sprintf(
            '%s-report-%s.pdf',
            $report,
            preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($period['label']))
        );

        $orientation = $report === 'income-expense' ? 'L' : 'P';

        return (new PdfGenerator())->download($html, $filename, [
            'title' => self::reportOptions()[$report] . ' - ' . $period['label'],
            'orientation' => $orientation,
        ]);
    }

    /**
     * Assembles the data each report template needs.
     *
     * @param string $report
     * @param ReportService $service
     * @param int $userId
     * @param array $period
     * @return array
     */
    private function buildReportData(string $report, ReportService $service, int $userId, array $period): array
    {
        $start = $period['start'];
        $end = $period['end'];

        switch ($report) {
            case 'category':
                return [
                    'expenseRows' => $service->categoryBreakdown($userId, $start, $end, 'expense'),
                    'incomeRows' => $service->categoryBreakdown($userId, $start, $end, 'income'),
                    'summary' => $service->summary($userId, $start, $end),
                ];

            case 'income-expense':
                return [
                    'trend' => $service->trend($userId, $start, $end),
                    'summary' => $service->summary($userId, $start, $end),
                ];

            case 'budget':
                return [
                    'budgets' => $service->budgetStatus($userId),
                ];

            case 'summary':
            default:
                return [
                    'summary' => $service->summary($userId, $start, $end),
                    'expenseRows' => $service->categoryBreakdown($userId, $start, $end, 'expense'),
                    'incomeRows' => $service->categoryBreakdown($userId, $start, $end, 'income'),
                ];
        }
    }

    /**
     * Branding/header metadata shared by all report templates.
     *
     * @return array
     */
    private function meta(): array
    {
        $identity = Yii::$app->user->identity;
        $settings = $identity ? \app\models\Settings::findOne(['user_id' => $identity->id]) : null;

        return [
            'appName' => Yii::$app->name,
            'company' => $settings->company_name ?? Yii::$app->name,
            'user' => $identity->username ?? '',
            'generatedAt' => Yii::$app->formatter->asDatetime(time(), 'medium'),
        ];
    }

    /**
     * Report type => label map.
     *
     * @return array
     */
    public static function reportOptions(): array
    {
        return [
            'summary' => Yii::t('app', 'Financial Summary'),
            'category' => Yii::t('app', 'Category Breakdown'),
            'income-expense' => Yii::t('app', 'Income vs Expense'),
            'budget' => Yii::t('app', 'Budget Status'),
        ];
    }

    /**
     * Period type => label map.
     *
     * @return array
     */
    public static function periodOptions(): array
    {
        return [
            ReportService::PERIOD_MONTH => Yii::t('app', 'Month'),
            ReportService::PERIOD_FISCAL => Yii::t('app', 'Fiscal Year'),
            ReportService::PERIOD_CUSTOM => Yii::t('app', 'Custom Range'),
            ReportService::PERIOD_LIFETIME => Yii::t('app', 'All Time'),
        ];
    }
}
