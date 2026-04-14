<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\models\User;
use app\models\Profile;
use app\models\Settings;
use app\models\IncomeCategory;
use app\models\ExpenseCategory;
use app\models\Income;
use app\models\Expense;

/**
 * SeedController - Database Seeder for Demo Data
 *
 * This controller provides commands to seed the database with realistic
 * demo data for screenshots, testing, and demonstrations.
 *
 * Usage:
 *   php yii seed/demo          - Create demo user with full data
 *   php yii seed/categories    - Seed only categories
 *   php yii seed/transactions  - Seed transactions for existing user
 *   php yii seed/clear         - Remove all demo data
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class SeedController extends Controller
{
    /**
     * @var string Demo user email
     */
    public $email = 'demo@example.com';

    /**
     * @var string Demo user password
     */
    public $password = 'demo123';

    /**
     * @var int Number of months of transaction history to generate
     */
    public $months = 12;

    /**
     * @var bool Whether to show verbose output
     */
    public $verbose = true;

    /**
     * {@inheritdoc}
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'email',
            'password',
            'months',
            'verbose',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'e' => 'email',
            'p' => 'password',
            'm' => 'months',
            'v' => 'verbose',
        ]);
    }

    /**
     * Seeds complete demo data including user, categories, and transactions
     *
     * Creates a demo user account with:
     * - Profile information
     * - Currency settings
     * - Income and expense categories
     * - 12 months of realistic transaction history
     *
     * @return int Exit code
     */
    public function actionDemo(): int
    {
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════════════╗\n", Console::FG_CYAN);
        $this->stdout("║           EXPENSE MANAGER - DEMO DATA SEEDER                ║\n", Console::FG_CYAN);
        $this->stdout("╚══════════════════════════════════════════════════════════════╝\n", Console::FG_CYAN);
        $this->stdout("\n");

        // Confirm action
        if (!$this->confirm('This will create demo data. Continue?')) {
            $this->stdout("Aborted.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Step 1: Create demo user
            $this->stdout("\n[1/5] Creating demo user...\n", Console::FG_GREEN);
            $user = $this->createDemoUser();
            $this->stdout("      ✓ User created: {$this->email}\n", Console::FG_GREEN);

            // Step 2: Create profile
            $this->stdout("[2/5] Creating user profile...\n", Console::FG_GREEN);
            $this->createDemoProfile($user);
            $this->stdout("      ✓ Profile created\n", Console::FG_GREEN);

            // Step 3: Create settings
            $this->stdout("[3/5] Creating user settings...\n", Console::FG_GREEN);
            $this->createDemoSettings($user);
            $this->stdout("      ✓ Settings configured\n", Console::FG_GREEN);

            // Step 4: Create categories
            $this->stdout("[4/5] Creating categories...\n", Console::FG_GREEN);
            $incomeCategories = $this->createIncomeCategories($user);
            $expenseCategories = $this->createExpenseCategories($user);
            $this->stdout('      ✓ ' . count($incomeCategories) . " income categories created\n", Console::FG_GREEN);
            $this->stdout('      ✓ ' . count($expenseCategories) . " expense categories created\n", Console::FG_GREEN);

            // Step 5: Create transactions
            $this->stdout("[5/5] Generating transactions ({$this->months} months)...\n", Console::FG_GREEN);
            $stats = $this->createTransactions($user, $incomeCategories, $expenseCategories);
            $this->stdout("      ✓ {$stats['incomes']} income records created\n", Console::FG_GREEN);
            $this->stdout("      ✓ {$stats['expenses']} expense records created\n", Console::FG_GREEN);

            $transaction->commit();

            // Success message
            $this->stdout("\n");
            $this->stdout("╔══════════════════════════════════════════════════════════════╗\n", Console::FG_GREEN);
            $this->stdout("║                    SEEDING COMPLETE!                         ║\n", Console::FG_GREEN);
            $this->stdout("╠══════════════════════════════════════════════════════════════╣\n", Console::FG_GREEN);
            $this->stdout("║  Demo Account Credentials:                                   ║\n", Console::FG_GREEN);
            $this->stdout("║                                                              ║\n", Console::FG_GREEN);
            $this->stdout("║  Email:    {$this->email}                            ║\n", Console::FG_YELLOW);
            $this->stdout("║  Password: {$this->password}                                         ║\n", Console::FG_YELLOW);
            $this->stdout("╚══════════════════════════════════════════════════════════════╝\n", Console::FG_GREEN);
            $this->stdout("\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("\nError: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Seeds only categories for an existing user
     *
     * @param int $userId User ID to seed categories for
     * @return int Exit code
     */
    public function actionCategories(int $userId = null): int
    {
        if ($userId === null) {
            $this->stderr("Please provide a user ID: php yii seed/categories <userId>\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $user = User::findOne($userId);
        if (!$user) {
            $this->stderr("User not found with ID: {$userId}\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout("Creating categories for user: {$user->username}...\n", Console::FG_CYAN);

        $incomeCategories = $this->createIncomeCategories($user);
        $expenseCategories = $this->createExpenseCategories($user);

        $this->stdout('✓ ' . count($incomeCategories) . " income categories created\n", Console::FG_GREEN);
        $this->stdout('✓ ' . count($expenseCategories) . " expense categories created\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Clears all demo data
     *
     * @return int Exit code
     */
    public function actionClear(): int
    {
        $this->stdout("\n", Console::FG_YELLOW);
        $this->stdout("⚠ WARNING: This will delete the demo user and ALL associated data!\n", Console::FG_YELLOW);
        $this->stdout("\n");

        if (!$this->confirm('Are you sure you want to delete all demo data?')) {
            $this->stdout("Aborted.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $user = User::findOne(['email' => $this->email]);

        if (!$user) {
            $this->stdout("Demo user not found: {$this->email}\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Delete in order (respecting foreign keys)
            Income::deleteAll(['user_id' => $user->id]);
            Expense::deleteAll(['user_id' => $user->id]);
            IncomeCategory::deleteAll(['user_id' => $user->id]);
            ExpenseCategory::deleteAll(['user_id' => $user->id]);
            Settings::deleteAll(['user_id' => $user->id]);
            Profile::deleteAll(['user_id' => $user->id]);
            $user->delete();

            $transaction->commit();

            $this->stdout("\n✓ Demo data cleared successfully!\n", Console::FG_GREEN);

            return ExitCode::OK;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("\nError: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Creates the demo user account
     *
     * @return User The created user
     * @throws \Exception If user creation fails
     */
    protected function createDemoUser(): User
    {
        // Check if user already exists
        $existingUser = User::findOne(['email' => $this->email]);
        if ($existingUser) {
            if ($this->confirm('Demo user already exists. Delete and recreate?')) {
                $this->actionClear();
            } else {
                throw new \Exception("Demo user already exists. Use 'php yii seed/clear' to remove first.");
            }
        }

        $user = new User();
        $user->username = 'demo';
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->created_at = time() - (365 * 24 * 60 * 60); // 1 year ago
        $user->updated_at = time();

        if (!$user->save()) {
            throw new \Exception('Failed to create user: ' . implode(', ', $user->getFirstErrors()));
        }

        return $user;
    }

    /**
     * Creates the demo user profile
     *
     * @param User $user The user to create profile for
     * @return Profile The created profile
     */
    protected function createDemoProfile(User $user): Profile
    {
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->name = 'Alex Johnson';
        $profile->designation = 'Financial Analyst';
        $profile->phone = '+1 (555) 123-4567';
        $profile->location = 'San Francisco, CA';
        $profile->website = 'https://alexjohnson.dev';
        $profile->timezone = 'America/Los_Angeles';
        $profile->bio = 'Personal finance enthusiast tracking expenses and building wealth through smart money management.';
        $profile->gravatar_email = 'demo@example.com';
        $profile->gravatar_id = md5(strtolower(trim('demo@example.com')));

        if (!$profile->save()) {
            throw new \Exception('Failed to create profile: ' . implode(', ', $profile->getFirstErrors()));
        }

        return $profile;
    }

    /**
     * Creates the demo user settings
     *
     * @param User $user The user to create settings for
     * @return Settings The created settings
     */
    protected function createDemoSettings(User $user): Settings
    {
        $settings = new Settings();
        $settings->user_id = $user->id;
        $settings->company_name = 'Personal Finance';
        $settings->currency = 'USD';
        $settings->currency_position = 'before';
        $settings->thousand_separator = ',';
        $settings->decimal_separator = '.';
        $settings->decimal_places = 2;
        $settings->timezone = 'America/Los_Angeles';
        $settings->date_format = 'M d, Y';
        $settings->time_format = 'h:i A';

        if (!$settings->save()) {
            throw new \Exception('Failed to create settings: ' . implode(', ', $settings->getFirstErrors()));
        }

        return $settings;
    }

    /**
     * Creates income categories with icons and colors
     *
     * @param User $user The user to create categories for
     * @return array Array of created IncomeCategory models
     */
    protected function createIncomeCategories(User $user): array
    {
        $categories = [
            ['name' => 'Salary', 'description' => 'Monthly salary and wages', 'icon' => 'bi-briefcase', 'color' => '#10b981'],
            ['name' => 'Freelance', 'description' => 'Freelance and consulting income', 'icon' => 'bi-laptop', 'color' => '#3b82f6'],
            ['name' => 'Investments', 'description' => 'Dividends, interest, and capital gains', 'icon' => 'bi-graph-up-arrow', 'color' => '#8b5cf6'],
            ['name' => 'Rental Income', 'description' => 'Property rental income', 'icon' => 'bi-house', 'color' => '#f59e0b'],
            ['name' => 'Business', 'description' => 'Business profits and revenue', 'icon' => 'bi-shop', 'color' => '#ec4899'],
            ['name' => 'Bonus', 'description' => 'Performance bonuses and incentives', 'icon' => 'bi-gift', 'color' => '#06b6d4'],
            ['name' => 'Commission', 'description' => 'Sales commissions', 'icon' => 'bi-percent', 'color' => '#84cc16'],
            ['name' => 'Refunds', 'description' => 'Tax refunds and reimbursements', 'icon' => 'bi-arrow-return-left', 'color' => '#64748b'],
            ['name' => 'Side Projects', 'description' => 'Income from side projects', 'icon' => 'bi-code-slash', 'color' => '#f97316'],
            ['name' => 'Other Income', 'description' => 'Miscellaneous income', 'icon' => 'bi-three-dots', 'color' => '#6b7280'],
        ];

        $created = [];
        foreach ($categories as $data) {
            $category = new IncomeCategory();
            $category->user_id = $user->id;
            $category->name = $data['name'];
            $category->description = $data['description'];
            $category->icon = $data['icon'];
            $category->color = $data['color'];
            $category->status = IncomeCategory::STATUS_ACTIVE;
            $category->created_at = time();
            $category->updated_at = time();
            $category->created_by = $user->id;
            $category->updated_by = $user->id;

            if ($category->save()) {
                $created[$data['name']] = $category;
            }
        }

        return $created;
    }

    /**
     * Creates expense categories with hierarchical structure
     *
     * @param User $user The user to create categories for
     * @return array Array of created ExpenseCategory models
     */
    protected function createExpenseCategories(User $user): array
    {
        // Parent categories
        $parentCategories = [
            ['name' => 'Housing and Utilities', 'description' => 'Home-related expenses', 'icon' => 'bi-house-door', 'color' => '#3b82f6'],
            ['name' => 'Transportation', 'description' => 'Vehicle and travel expenses', 'icon' => 'bi-car-front', 'color' => '#8b5cf6'],
            ['name' => 'Food and Dining', 'description' => 'Groceries and restaurants', 'icon' => 'bi-cup-hot', 'color' => '#f59e0b'],
            ['name' => 'Healthcare', 'description' => 'Medical and health expenses', 'icon' => 'bi-heart-pulse', 'color' => '#ef4444'],
            ['name' => 'Personal', 'description' => 'Personal care and lifestyle', 'icon' => 'bi-person', 'color' => '#ec4899'],
            ['name' => 'Entertainment', 'description' => 'Leisure and entertainment', 'icon' => 'bi-controller', 'color' => '#06b6d4'],
            ['name' => 'Education', 'description' => 'Learning and development', 'icon' => 'bi-mortarboard', 'color' => '#10b981'],
            ['name' => 'Financial', 'description' => 'Banking and financial services', 'icon' => 'bi-bank', 'color' => '#64748b'],
        ];

        // Child categories (mapped to parent names)
        $childCategories = [
            'Housing and Utilities' => [
                ['name' => 'Rent/Mortgage', 'icon' => 'bi-house', 'color' => '#3b82f6'],
                ['name' => 'Electricity', 'icon' => 'bi-lightning', 'color' => '#fbbf24'],
                ['name' => 'Water', 'icon' => 'bi-droplet', 'color' => '#0ea5e9'],
                ['name' => 'Gas', 'icon' => 'bi-fire', 'color' => '#f97316'],
                ['name' => 'Internet and Cable', 'icon' => 'bi-wifi', 'color' => '#6366f1'],
                ['name' => 'Property Taxes', 'icon' => 'bi-receipt', 'color' => '#78716c'],
                ['name' => 'Home Insurance', 'icon' => 'bi-shield-check', 'color' => '#22c55e'],
                ['name' => 'Home Maintenance', 'icon' => 'bi-tools', 'color' => '#a855f7'],
            ],
            'Transportation' => [
                ['name' => 'Gasoline and Fuel', 'icon' => 'bi-fuel-pump', 'color' => '#ef4444'],
                ['name' => 'Car Payment', 'icon' => 'bi-car-front', 'color' => '#8b5cf6'],
                ['name' => 'Car Insurance', 'icon' => 'bi-shield', 'color' => '#3b82f6'],
                ['name' => 'Maintenance and Repairs', 'icon' => 'bi-wrench', 'color' => '#78716c'],
                ['name' => 'Public Transit', 'icon' => 'bi-bus-front', 'color' => '#06b6d4'],
                ['name' => 'Parking', 'icon' => 'bi-p-circle', 'color' => '#64748b'],
                ['name' => 'Ride Sharing', 'icon' => 'bi-taxi-front', 'color' => '#fbbf24'],
            ],
            'Food and Dining' => [
                ['name' => 'Groceries', 'icon' => 'bi-cart', 'color' => '#22c55e'],
                ['name' => 'Restaurants', 'icon' => 'bi-cup-straw', 'color' => '#f97316'],
                ['name' => 'Coffee Shops', 'icon' => 'bi-cup-hot', 'color' => '#a16207'],
                ['name' => 'Food Delivery', 'icon' => 'bi-bag', 'color' => '#ec4899'],
                ['name' => 'Snacks and Coffee', 'icon' => 'bi-cup', 'color' => '#78716c'],
            ],
            'Healthcare' => [
                ['name' => 'Health Insurance', 'icon' => 'bi-heart', 'color' => '#ef4444'],
                ['name' => 'Doctor Visits', 'icon' => 'bi-hospital', 'color' => '#3b82f6'],
                ['name' => 'Medications', 'icon' => 'bi-capsule', 'color' => '#10b981'],
                ['name' => 'Dental', 'icon' => 'bi-emoji-smile', 'color' => '#0ea5e9'],
                ['name' => 'Vision', 'icon' => 'bi-eye', 'color' => '#8b5cf6'],
                ['name' => 'Gym Memberships', 'icon' => 'bi-bicycle', 'color' => '#f59e0b'],
            ],
            'Personal' => [
                ['name' => 'Clothing and Accessories', 'icon' => 'bi-bag-heart', 'color' => '#ec4899'],
                ['name' => 'Personal Care', 'icon' => 'bi-scissors', 'color' => '#a855f7'],
                ['name' => 'Haircuts', 'icon' => 'bi-scissors', 'color' => '#6366f1'],
                ['name' => 'Gifts', 'icon' => 'bi-gift', 'color' => '#f43f5e'],
                ['name' => 'Donations', 'icon' => 'bi-heart-fill', 'color' => '#ef4444'],
            ],
            'Entertainment' => [
                ['name' => 'Streaming Services', 'icon' => 'bi-play-circle', 'color' => '#e11d48'],
                ['name' => 'Movies and Events', 'icon' => 'bi-film', 'color' => '#8b5cf6'],
                ['name' => 'Games', 'icon' => 'bi-controller', 'color' => '#3b82f6'],
                ['name' => 'Hobbies', 'icon' => 'bi-palette', 'color' => '#f59e0b'],
                ['name' => 'Vacations', 'icon' => 'bi-airplane', 'color' => '#06b6d4'],
                ['name' => 'Books and Media', 'icon' => 'bi-book', 'color' => '#78716c'],
            ],
            'Education' => [
                ['name' => 'Tuition and School Fees', 'icon' => 'bi-mortarboard', 'color' => '#10b981'],
                ['name' => 'Books and Supplies', 'icon' => 'bi-journal-text', 'color' => '#3b82f6'],
                ['name' => 'Online Courses', 'icon' => 'bi-laptop', 'color' => '#8b5cf6'],
                ['name' => 'Certifications', 'icon' => 'bi-award', 'color' => '#f59e0b'],
            ],
            'Financial' => [
                ['name' => 'Bank Fees', 'icon' => 'bi-bank2', 'color' => '#64748b'],
                ['name' => 'Credit Card Fees', 'icon' => 'bi-credit-card', 'color' => '#3b82f6'],
                ['name' => 'Loan Payments', 'icon' => 'bi-cash-stack', 'color' => '#ef4444'],
                ['name' => 'Investment Fees', 'icon' => 'bi-graph-up', 'color' => '#10b981'],
                ['name' => 'Taxes', 'icon' => 'bi-receipt', 'color' => '#78716c'],
            ],
        ];

        // Additional standalone categories
        $standaloneCategories = [
            ['name' => 'Pet Expenses', 'description' => 'Pet food, vet, and supplies', 'icon' => 'bi-heart', 'color' => '#f97316'],
            ['name' => 'Childcare', 'description' => 'Daycare and child-related expenses', 'icon' => 'bi-people', 'color' => '#ec4899'],
            ['name' => 'Subscriptions', 'description' => 'Monthly subscriptions', 'icon' => 'bi-repeat', 'color' => '#6366f1'],
            ['name' => 'Miscellaneous', 'description' => 'Other uncategorized expenses', 'icon' => 'bi-three-dots', 'color' => '#6b7280'],
        ];

        $created = [];

        // Create parent categories first
        foreach ($parentCategories as $data) {
            $category = new ExpenseCategory();
            $category->user_id = $user->id;
            $category->parent_id = null;
            $category->name = $data['name'];
            $category->description = $data['description'];
            $category->icon = $data['icon'];
            $category->color = $data['color'];
            $category->status = ExpenseCategory::STATUS_ACTIVE;
            $category->created_at = time();
            $category->updated_at = time();
            $category->created_by = $user->id;
            $category->updated_by = $user->id;

            if ($category->save()) {
                $created[$data['name']] = $category;

                // Create child categories
                if (isset($childCategories[$data['name']])) {
                    foreach ($childCategories[$data['name']] as $childData) {
                        $child = new ExpenseCategory();
                        $child->user_id = $user->id;
                        $child->parent_id = $category->id;
                        $child->name = $childData['name'];
                        $child->description = 'Sub-category of ' . $data['name'];
                        $child->icon = $childData['icon'];
                        $child->color = $childData['color'];
                        $child->status = ExpenseCategory::STATUS_ACTIVE;
                        $child->created_at = time();
                        $child->updated_at = time();
                        $child->created_by = $user->id;
                        $child->updated_by = $user->id;

                        if ($child->save()) {
                            $created[$childData['name']] = $child;
                        }
                    }
                }
            }
        }

        // Create standalone categories
        foreach ($standaloneCategories as $data) {
            $category = new ExpenseCategory();
            $category->user_id = $user->id;
            $category->parent_id = null;
            $category->name = $data['name'];
            $category->description = $data['description'];
            $category->icon = $data['icon'];
            $category->color = $data['color'];
            $category->status = ExpenseCategory::STATUS_ACTIVE;
            $category->created_at = time();
            $category->updated_at = time();
            $category->created_by = $user->id;
            $category->updated_by = $user->id;

            if ($category->save()) {
                $created[$data['name']] = $category;
            }
        }

        return $created;
    }

    /**
     * Creates realistic transaction data for the specified number of months
     *
     * Generates transactions from X months ago up to the current date.
     *
     * @param User $user The user to create transactions for
     * @param array $incomeCategories Array of income categories
     * @param array $expenseCategories Array of expense categories
     * @return array Statistics about created transactions
     */
    protected function createTransactions(User $user, array $incomeCategories, array $expenseCategories): array
    {
        $incomeCount = 0;
        $expenseCount = 0;

        // Current date
        $today = time();
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');
        $currentDay = (int) date('j');

        // Start from X months ago (from the 1st of that month)
        $startDate = strtotime("-{$this->months} months", strtotime('first day of this month'));

        for ($m = 0; $m <= $this->months; $m++) {
            $monthStart = strtotime("+{$m} months", $startDate);
            $monthYear = (int) date('Y', $monthStart);
            $monthNum = (int) date('n', $monthStart);
            $monthName = date('F Y', $monthStart);

            // Skip if month is in the future
            if ($monthYear > $currentYear || ($monthYear === $currentYear && $monthNum > $currentMonth)) {
                continue;
            }

            // Determine the last day to generate transactions for this month
            $isCurrentMonth = ($monthYear === $currentYear && $monthNum === $currentMonth);
            $lastDayOfMonth = $isCurrentMonth ? $currentDay : (int) date('t', $monthStart);

            if ($this->verbose) {
                $this->stdout("      Generating {$monthName}...\n", Console::FG_CYAN);
            }

            // Generate income for this month
            $incomeCount += $this->generateMonthlyIncome($user, $incomeCategories, $monthStart, $lastDayOfMonth, $isCurrentMonth);

            // Generate expenses for this month
            $expenseCount += $this->generateMonthlyExpenses($user, $expenseCategories, $monthStart, $lastDayOfMonth, $isCurrentMonth);
        }

        return [
            'incomes' => $incomeCount,
            'expenses' => $expenseCount,
        ];
    }

    /**
     * Generates income records for a specific month
     *
     * @param User $user The user
     * @param array $categories Income categories
     * @param int $monthStart Month start timestamp
     * @param int $lastDay Last day of month to generate (for current month, this is today)
     * @param bool $isCurrentMonth Whether this is the current month
     * @return int Number of income records created
     */
    protected function generateMonthlyIncome(User $user, array $categories, int $monthStart, int $lastDay, bool $isCurrentMonth = false): int
    {
        $count = 0;

        // Regular salary (1st of month) - only if day 1 has passed
        if (isset($categories['Salary']) && $lastDay >= 1) {
            $this->createIncome($user, $categories['Salary'], $monthStart, $this->randomAmount(7500, 8500), 'Monthly Salary');
            $count++;
        }

        // Occasional freelance income (random months)
        if (isset($categories['Freelance']) && rand(1, 100) <= 60) {
            $day = rand(5, min(25, $lastDay));
            if ($day <= $lastDay) {
                $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
                $this->createIncome($user, $categories['Freelance'], $date, $this->randomAmount(500, 3000), 'Freelance Project');
                $count++;
            }
        }

        // Quarterly investment dividends
        $month = (int) date('n', $monthStart);
        if (isset($categories['Investments']) && in_array($month, [3, 6, 9, 12]) && $lastDay >= 14) {
            $this->createIncome($user, $categories['Investments'], strtotime('+14 days', $monthStart), $this->randomAmount(200, 800), 'Quarterly Dividends');
            $count++;
        }

        // Annual bonus (December or January)
        if (isset($categories['Bonus']) && in_array($month, [12, 1]) && rand(1, 100) <= 50 && $lastDay >= 10) {
            $this->createIncome($user, $categories['Bonus'], strtotime('+10 days', $monthStart), $this->randomAmount(3000, 10000), 'Annual Performance Bonus');
            $count++;
        }

        // Occasional side project income
        if (isset($categories['Side Projects']) && rand(1, 100) <= 30) {
            $day = rand(1, min(28, $lastDay));
            $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
            $this->createIncome($user, $categories['Side Projects'], $date, $this->randomAmount(100, 500), 'Side Project Revenue');
            $count++;
        }

        // Random refunds
        if (isset($categories['Refunds']) && rand(1, 100) <= 15) {
            $day = rand(1, min(28, $lastDay));
            $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
            $this->createIncome($user, $categories['Refunds'], $date, $this->randomAmount(25, 200), 'Refund');
            $count++;
        }

        return $count;
    }

    /**
     * Generates expense records for a specific month
     *
     * @param User $user The user
     * @param array $categories Expense categories
     * @param int $monthStart Month start timestamp
     * @param int $lastDay Last day of month to generate (for current month, this is today)
     * @param bool $isCurrentMonth Whether this is the current month
     * @return int Number of expense records created
     */
    protected function generateMonthlyExpenses(User $user, array $categories, int $monthStart, int $lastDay, bool $isCurrentMonth = false): int
    {
        $count = 0;

        // Fixed monthly expenses
        $fixedExpenses = [
            'Rent/Mortgage' => ['amount' => [2200, 2200], 'day' => 1, 'desc' => 'Monthly Rent'],
            'Electricity' => ['amount' => [80, 180], 'day' => 15, 'desc' => 'Electric Bill'],
            'Water' => ['amount' => [40, 70], 'day' => 10, 'desc' => 'Water Bill'],
            'Gas' => ['amount' => [30, 90], 'day' => 12, 'desc' => 'Gas Bill'],
            'Internet and Cable' => ['amount' => [89, 129], 'day' => 5, 'desc' => 'Internet Service'],
            'Car Insurance' => ['amount' => [120, 150], 'day' => 1, 'desc' => 'Auto Insurance'],
            'Health Insurance' => ['amount' => [350, 450], 'day' => 1, 'desc' => 'Health Insurance Premium'],
            'Gym Memberships' => ['amount' => [40, 60], 'day' => 1, 'desc' => 'Gym Membership'],
            'Streaming Services' => ['amount' => [45, 65], 'day' => 8, 'desc' => 'Streaming Subscriptions'],
        ];

        foreach ($fixedExpenses as $catName => $config) {
            if (isset($categories[$catName]) && $config['day'] <= $lastDay) {
                $day = $config['day'];
                $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
                $this->createExpense($user, $categories[$catName], $date, $this->randomAmount($config['amount'][0], $config['amount'][1]), $config['desc']);
                $count++;
            }
        }

        // Variable expenses (multiple per month)
        $variableExpenses = [
            'Groceries' => ['times' => [4, 6], 'amount' => [80, 200], 'desc' => 'Grocery Shopping'],
            'Restaurants' => ['times' => [3, 8], 'amount' => [25, 80], 'desc' => 'Restaurant'],
            'Gasoline and Fuel' => ['times' => [3, 5], 'amount' => [40, 70], 'desc' => 'Gas Fill-up'],
            'Coffee Shops' => ['times' => [5, 15], 'amount' => [5, 15], 'desc' => 'Coffee'],
            'Food Delivery' => ['times' => [2, 5], 'amount' => [20, 50], 'desc' => 'Food Delivery'],
        ];

        foreach ($variableExpenses as $catName => $config) {
            if (isset($categories[$catName])) {
                // Adjust times for current month based on how many days have passed
                $maxTimes = $isCurrentMonth
                    ? max(1, (int) ($config['times'][1] * $lastDay / 30))
                    : $config['times'][1];
                $minTimes = $isCurrentMonth
                    ? max(1, (int) ($config['times'][0] * $lastDay / 30))
                    : $config['times'][0];

                $times = rand($minTimes, $maxTimes);
                for ($i = 0; $i < $times; $i++) {
                    $day = rand(1, $lastDay);
                    $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
                    $this->createExpense($user, $categories[$catName], $date, $this->randomAmount($config['amount'][0], $config['amount'][1]), $config['desc']);
                    $count++;
                }
            }
        }

        // Occasional expenses
        $occasionalExpenses = [
            'Clothing and Accessories' => ['chance' => 40, 'amount' => [50, 200], 'desc' => 'Clothing Purchase'],
            'Personal Care' => ['chance' => 60, 'amount' => [20, 80], 'desc' => 'Personal Care'],
            'Haircuts' => ['chance' => 33, 'amount' => [25, 50], 'desc' => 'Haircut'],
            'Gifts' => ['chance' => 25, 'amount' => [30, 150], 'desc' => 'Gift'],
            'Movies and Events' => ['chance' => 50, 'amount' => [15, 60], 'desc' => 'Entertainment'],
            'Books and Media' => ['chance' => 40, 'amount' => [10, 40], 'desc' => 'Book Purchase'],
            'Online Courses' => ['chance' => 15, 'amount' => [50, 200], 'desc' => 'Online Course'],
            'Home Maintenance' => ['chance' => 20, 'amount' => [50, 300], 'desc' => 'Home Repair'],
            'Maintenance and Repairs' => ['chance' => 15, 'amount' => [100, 500], 'desc' => 'Car Maintenance'],
            'Doctor Visits' => ['chance' => 20, 'amount' => [30, 150], 'desc' => 'Doctor Visit'],
            'Medications' => ['chance' => 30, 'amount' => [15, 80], 'desc' => 'Pharmacy'],
            'Pet Expenses' => ['chance' => 60, 'amount' => [30, 100], 'desc' => 'Pet Supplies'],
            'Donations' => ['chance' => 25, 'amount' => [25, 100], 'desc' => 'Charitable Donation'],
        ];

        foreach ($occasionalExpenses as $catName => $config) {
            // Reduce chance for current month based on days passed
            $chance = $isCurrentMonth ? (int) ($config['chance'] * $lastDay / 30) : $config['chance'];

            if (isset($categories[$catName]) && rand(1, 100) <= $chance) {
                $day = rand(1, $lastDay);
                $date = strtotime(date('Y-m-', $monthStart) . sprintf('%02d', $day));
                $this->createExpense($user, $categories[$catName], $date, $this->randomAmount($config['amount'][0], $config['amount'][1]), $config['desc']);
                $count++;
            }
        }

        // Quarterly expenses
        $month = (int) date('n', $monthStart);
        if (isset($categories['Property Taxes']) && in_array($month, [3, 6, 9, 12]) && $lastDay >= 10) {
            $this->createExpense($user, $categories['Property Taxes'], strtotime('+10 days', $monthStart), $this->randomAmount(800, 1200), 'Property Tax');
            $count++;
        }

        // Annual expenses (spread across specific months)
        if (isset($categories['Home Insurance']) && $month === 6 && $lastDay >= 5) {
            $this->createExpense($user, $categories['Home Insurance'], strtotime('+5 days', $monthStart), $this->randomAmount(1200, 1800), 'Annual Home Insurance');
            $count++;
        }

        if (isset($categories['Vacations']) && in_array($month, [7, 12]) && rand(1, 100) <= 70 && $lastDay >= 15) {
            $this->createExpense($user, $categories['Vacations'], strtotime('+15 days', $monthStart), $this->randomAmount(500, 3000), 'Vacation Expenses');
            $count++;
        }

        return $count;
    }

    /**
     * Creates a single income record
     *
     * @param User $user The user
     * @param IncomeCategory $category The category
     * @param int $date The date timestamp
     * @param float $amount The amount
     * @param string $description The description
     * @return Income|null The created income or null
     */
    protected function createIncome(User $user, IncomeCategory $category, int $date, float $amount, string $description): ?Income
    {
        $income = new Income();
        $income->user_id = $user->id;
        $income->income_category_id = $category->id;
        $income->entry_date = date('Y-m-d', $date);
        $income->amount = (string) number_format($amount, 2, '.', ''); // Cast to string for validation
        $income->description = $description;
        $income->reference = 'INC-' . strtoupper(Yii::$app->security->generateRandomString(6));
        $income->created_at = $date;
        $income->updated_at = $date;
        $income->created_by = $user->id;
        $income->updated_by = $user->id;

        if (!$income->save()) {
            if ($this->verbose) {
                $this->stderr('      ✗ Income save failed: ' . json_encode($income->getErrors()) . "\n", Console::FG_RED);
            }
            return null;
        }

        return $income;
    }

    /**
     * Creates a single expense record
     *
     * @param User $user The user
     * @param ExpenseCategory $category The category
     * @param int $date The date timestamp
     * @param float $amount The amount
     * @param string $description The description
     * @return Expense|null The created expense or null
     */
    protected function createExpense(User $user, ExpenseCategory $category, int $date, float $amount, string $description): ?Expense
    {
        $paymentMethods = ['Cash', 'Card', 'Bank'];

        $expense = new Expense();
        $expense->user_id = $user->id;
        $expense->expense_category_id = $category->id;
        $expense->expense_date = date('Y-m-d', $date);
        $expense->amount = (string) number_format($amount, 2, '.', ''); // Cast to string for validation
        $expense->description = $description;
        $expense->reference = 'EXP-' . strtoupper(Yii::$app->security->generateRandomString(6));
        $expense->payment_method = $paymentMethods[array_rand($paymentMethods)];
        $expense->created_at = $date;
        $expense->updated_at = $date;
        $expense->created_by = $user->id;
        $expense->updated_by = $user->id;

        if (!$expense->save()) {
            if ($this->verbose) {
                $this->stderr('      ✗ Expense save failed: ' . json_encode($expense->getErrors()) . "\n", Console::FG_RED);
            }
            return null;
        }

        return $expense;
    }

    /**
     * Generates a random amount within a range
     *
     * @param float $min Minimum amount
     * @param float $max Maximum amount
     * @return float Random amount with 2 decimal places
     */
    protected function randomAmount(float $min, float $max): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }
}
