<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\models\LoginForm;
use app\models\SignupForm;
use app\models\ResetPasswordForm;
use app\models\VerifyPasswordForm;
use app\models\ExpenseCategory;
use app\models\Expense;
use app\actions\CustomErrorAction;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\base\InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * SiteController - Handles core application functionality
 *
 * This controller provides:
 * - Dashboard page rendering
 * - User authentication (login, logout, signup, password reset)
 * - Excel report generation for fiscal year expenses
 * - Error handling and captcha support
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     *
     * Configures access control and verb filters:
     * - Index and logout require authenticated users
     * - Logout only accepts POST requests
     *
     * @return array The behavior configurations
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'index'],
                'rules' => [
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Declares external actions:
     * - error: Custom error page with dedicated layout
     * - captcha: CAPTCHA for form validation
     *
     * @return array The action configurations
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => CustomErrorAction::class,
                'layout' => 'error',
                'errorAssets' => \app\assets\ErrorAsset::class,
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays the main dashboard
     *
     * Renders the dashboard view containing widgetized components
     * that handle their own data loading independently.
     *
     * @return string The rendered dashboard view
     */
    public function actionIndex()
    {
        $selectedFY = Yii::$app->request->get('fy');

        $vm = new \app\viewmodels\DashboardViewModel($selectedFY);

        return $this->render('index', compact('vm'));
    }

    // =========================================================================
    // EXCEL EXPORT METHODS
    // =========================================================================

    /**
     * Exports fiscal year expenses to Excel spreadsheet
     *
     * Generates a comprehensive Excel report containing:
     * - Monthly breakdown by expense category
     * - Category totals across all months
     * - Grand total for the fiscal year
     *
     * Report Structure:
     * - Rows: Months (July to June)
     * - Columns: Expense categories
     * - Footer: Category totals and grand total
     *
     * @return void Outputs Excel file directly to browser for download
     */
    public function actionExportExpenses()
    {
        $this->disableDebugModule();
        $this->clearOutputBuffers();

        $fiscalStart = '2024-07-01';
        $fiscalEnd = '2025-06-30';

        $months = $this->buildMonthsArray($fiscalStart, $fiscalEnd);
        $categoryMap = $this->getCategoryMap();
        $pivotData = $this->buildPivotTableData($months, $categoryMap);

        $this->generateExpenseExcel($months, $categoryMap, $pivotData);
    }

    /**
     * Disables the Yii debug module
     *
     * Prevents the debug toolbar from corrupting binary file downloads.
     *
     * @return void
     */
    protected function disableDebugModule()
    {
        if (Yii::$app->hasModule('debug')) {
            Yii::$app->getModule('debug')->instance = null;
        }
    }

    /**
     * Clears all PHP output buffers
     *
     * Ensures clean output for binary file downloads.
     *
     * @return void
     */
    protected function clearOutputBuffers()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Builds an array of months for the fiscal year period
     *
     * @param string $startDate Fiscal year start date (Y-m-d)
     * @param string $endDate Fiscal year end date (Y-m-d)
     * @return array Associative array [Y-m => 'Month Year']
     */
    protected function buildMonthsArray($startDate, $endDate)
    {
        $months = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($start <= $end) {
            $ym = $start->format('Y-m');
            $months[$ym] = $start->format('F Y');
            $start->modify('+1 month');
        }

        return $months;
    }

    /**
     * Retrieves expense category ID to name mapping
     *
     * @return array Associative array [category_id => category_name]
     */
    protected function getCategoryMap()
    {
        $categories = ExpenseCategory::find()
            ->select(['id', 'name'])
            ->where(['user_id' => Yii::$app->user->id])
            ->asArray()
            ->all();

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['id']] = $cat['name'];
        }

        return $categoryMap;
    }

    /**
     * Builds pivot table data for the expense report
     *
     * Creates a matrix of expenses by month and category
     * along with category totals and grand total.
     *
     * @param array $months Months array from buildMonthsArray()
     * @param array $categoryMap Category mapping from getCategoryMap()
     * @return array Pivot data [pivot, totals, grandTotal]
     */
    protected function buildPivotTableData($months, $categoryMap)
    {
        $pivot = [];
        $totals = [];

        foreach ($months as $ym => $label) {
            $firstDay = "{$ym}-01";
            $lastDay = date('Y-m-t', strtotime($firstDay));

            foreach ($categoryMap as $catId => $catName) {
                $pivot[$ym][$catId] = 0;
            }

            $expenses = Expense::find()
                ->select(['expense_category_id', 'SUM(amount) AS total'])
                ->where(['between', 'expense_date', $firstDay, $lastDay])
                ->andWhere(['user_id' => Yii::$app->user->id])
                ->groupBy('expense_category_id')
                ->asArray()
                ->all();

            foreach ($expenses as $expense) {
                $catId = $expense['expense_category_id'];
                $amount = (float)$expense['total'];

                $pivot[$ym][$catId] = $amount;
                $totals[$catId] = ($totals[$catId] ?? 0) + $amount;
            }
        }

        return [
            'pivot' => $pivot,
            'totals' => $totals,
            'grandTotal' => array_sum($totals),
        ];
    }

    /**
     * Generates and outputs the Excel expense report
     *
     * Creates a formatted Excel spreadsheet with header,
     * data rows, totals, and professional styling.
     *
     * @param array $months Months array
     * @param array $categoryMap Category mapping
     * @param array $pivotData Pivot table data
     * @return void Outputs file to browser
     */
    protected function generateExpenseExcel($months, $categoryMap, $pivotData)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expense Report');

        // Header row
        $sheet->setCellValue('A1', 'Month');
        $colIndex = 2;

        foreach ($categoryMap as $catName) {
            $cleanName = preg_replace('/[└├│─┬┼┤┐┘┌└]/u', '', $catName);
            $cleanName = trim($cleanName);
            $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValueExplicit("{$colLetter}1", $cleanName, DataType::TYPE_STRING);
        }

        $this->applyHeaderStyle($sheet, $colIndex - 1);

        // Data rows
        $rowIndex = 2;
        foreach ($months as $ym => $label) {
            $sheet->setCellValue("A{$rowIndex}", $label);
            $colIndex = 2;

            foreach (array_keys($categoryMap) as $catId) {
                $value = $pivotData['pivot'][$ym][$catId] ?? 0;
                $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                $sheet->setCellValue("{$colLetter}{$rowIndex}", $value);
            }
            $rowIndex++;
        }

        // Totals row
        $sheet->setCellValue("A{$rowIndex}", 'Category Totals');
        $colIndex = 2;
        foreach (array_keys($categoryMap) as $catId) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$colLetter}{$rowIndex}", $pivotData['totals'][$catId] ?? 0);
        }

        $this->applyTotalsStyle($sheet, $rowIndex, $colIndex - 1);

        // Grand total
        $rowIndex++;
        $sheet->setCellValue("A{$rowIndex}", 'Grand Total');
        $sheet->setCellValue("B{$rowIndex}", $pivotData['grandTotal']);
        $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFont()->setBold(true);

        $this->autoSizeColumns($sheet, $colIndex - 1);
        $this->outputExcelFile($spreadsheet, 'Expense-Report-FY2024-2025.xlsx');
    }

    /**
     * Applies styling to the header row
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet
     * @param int $lastColumn Last column index
     * @return void
     */
    protected function applyHeaderStyle($sheet, $lastColumn)
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($lastColumn);

        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => '2563EB'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    /**
     * Applies styling to the totals row
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet
     * @param int $rowIndex The totals row index
     * @param int $lastColumn Last column index
     * @return void
     */
    protected function applyTotalsStyle($sheet, $rowIndex, $lastColumn)
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($lastColumn);

        $sheet->getStyle("A{$rowIndex}:{$lastColLetter}{$rowIndex}")->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'F3F4F6'],
            ],
        ]);
    }

    /**
     * Auto-sizes all columns in the worksheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet The worksheet
     * @param int $lastColumn Last column index
     * @return void
     */
    protected function autoSizeColumns($sheet, $lastColumn)
    {
        for ($col = 1; $col <= $lastColumn; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Outputs the Excel file to the browser for download
     *
     * @param Spreadsheet $spreadsheet The spreadsheet object
     * @param string $filename The output filename
     * @return void
     */
    protected function outputExcelFile($spreadsheet, $filename)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Transfer-Encoding: binary');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // =========================================================================
    // AUTHENTICATION METHODS
    // =========================================================================

    /**
     * Displays the login page
     *
     * Handles user authentication:
     * - Redirects authenticated users to home
     * - Validates login credentials
     * - Uses dedicated auth layout
     *
     * @return Response|string Redirect or rendered login form
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        $this->layout = 'auth';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Displays the signup/registration page
     *
     * Handles new user registration:
     * - Redirects authenticated users to home
     * - Validates registration data
     * - Creates new user account
     * - Optionally sends verification email
     * - Auto-logs in user after successful registration
     *
     * @return Response|string Redirect or rendered signup form
     */
    public function actionSignup()
    {
        // Redirect if already logged in
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new SignupForm();

        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'Welcome! Your account has been created successfully.')
            );

            // Auto-login after registration (optional)
            // If you want email verification first, remove this block
            if (Yii::$app->user->login($model->getUser())) {
                return $this->goHome();
            }

            return $this->redirect(['site/login']);
        }

        $this->layout = 'auth';

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user
     *
     * Terminates the user session and redirects to home page.
     * Only accepts POST requests to prevent CSRF attacks.
     *
     * @return Response Redirect to home page
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays the forgot password page
     *
     * Handles password reset request:
     * - Validates email address
     * - Sends password reset email with token
     * - Redirects to verification page on success
     *
     * @return Response|string Redirect or rendered form
     */
    public function actionForgotPassword()
    {
        $this->layout = 'auth';

        $model = new ResetPasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $token = $model->sendEmail();

            if ($token) {
                Yii::$app->session->setFlash(
                    'success',
                    Yii::t('app', 'Check your email for further instructions.')
                );
                return $this->redirect(['site/verify-password', 'token' => $token]);
            }

            Yii::$app->session->setFlash(
                'error',
                Yii::t('app', 'Sorry, we are unable to reset password for the provided email address.')
            );
        }

        return $this->render('forgot-password', [
            'model' => $model,
        ]);
    }

    /**
     * Handles password reset verification and update
     *
     * Validates the reset token and allows setting a new password.
     *
     * @param string $token The password reset token
     * @return Response|string Redirect on success or rendered form
     * @throws BadRequestHttpException If token is invalid or expired
     */
    public function actionVerifyPassword($token)
    {
        $this->layout = 'auth';

        try {
            $model = new VerifyPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'New password saved successfully.')
            );

            return $this->goHome();
        }

        return $this->render('verify-password', [
            'model' => $model,
        ]);
    }

    /**
     * Displays the about page
     *
     * Shows information about the application including version,
     * features, technology stack, and links to GitHub repository.
     *
     * @return string The rendered about view
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
