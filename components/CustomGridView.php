<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\GridView;

/**
 * CustomGridView extends Yii2's GridView with custom thead styling,
 * a page-size dropdown, and an inline search field.
 *
 * @package app\components
 */
class CustomGridView extends GridView
{
    /** @var array HTML options for the tbody element */
    public array $tbodyOptions = [];

    /** @var \yii\base\Model|null The search model used for the page-size and search fields */
    public $searchModel = null;

    /**
     * Renders the table header.
     *
     * @return string the rendering result
     */
    public function renderTableHeader(): string
    {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column \yii\grid\Column */
            $cells[] = $column->renderHeaderCell();
        }
        $content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
        if ($this->filterPosition === self::FILTER_POS_HEADER) {
            $content = $this->renderFilters() . $content;
        } elseif ($this->filterPosition === self::FILTER_POS_BODY) {
            $content .= $this->renderFilters();
        }

        return "<thead class='text-muted table-light'>\n" . $content . "\n</thead>";
    }

    /**
     * Renders the table body.
     *
     * @return string the rendering result
     */
    public function renderTableBody(): string
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();
        $rows = [];
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($model, $key, $index);

            if ($this->afterRow !== null) {
                $row = call_user_func($this->afterRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows) && $this->emptyText !== false) {
            $colspan = count($this->columns);

            return '<tbody' . \yii\helpers\Html::renderTagAttributes($this->tbodyOptions) . ">\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        }

        return '<tbody' . \yii\helpers\Html::renderTagAttributes($this->tbodyOptions) . ">\n" . implode("\n", $rows) . "\n</tbody>";
    }

    /**
     * Renders the number of entries dropdown.
     *
     * @return string the rendering result
     */
    public function renderNumberOfEntries(): string
    {
        ob_start();

        $form = ActiveForm::begin([
            'action' => ['index'],
            'id' => 'pageSizeForm',
            'method' => 'get',
            'options' => [
                'data-pjax' => 1,
            ],
        ]);
        echo $form->field($this->searchModel, 'pageSize', [
            'template' => '
                <div class="d-flex align-items-center gap-2 field-pageSizeDropdown">
                    <span class="text-muted">Show:</span> {input} <span class="text-muted">Entries</span>
                </div>',
            'options' => [
                'tag' => false,
            ],
        ])->dropDownList([
            '100' => '100',
            '200' => '200',
            '300' => '300',
            '400' => '400',
        ], ['id' => 'pageSizeDropdown', 'class' => 'form-select d-inline'])->label(false);
        ActiveForm::end();

        return ob_get_clean();
    }

    /**
     * Renders the search input field.
     *
     * @return string the rendering result
     */
    public function renderSearch(): string
    {
        ob_start();

        $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
            'options' => [
                'data-pjax' => 1,
            ],
        ]);

        echo '<div class="search-box">';
        echo $form->field($this->searchModel, 's')->textInput(['placeholder' => 'Search for name or something...', 'class' => 'form-control search'])->label(false);
        echo '<i class="ri-search-line search-icon"></i>';
        echo '</div>';

        ActiveForm::end();

        return ob_get_clean();
    }

    /**
     * Renders the GridView with custom layout tokens.
     *
     * @return void
     */
    public function run(): void
    {
        $this->layout = strtr($this->layout, [
            '{numberofentries}' => $this->renderNumberOfEntries(),
            '{search}' => $this->renderSearch(),
        ]);

        parent::run();
    }
}
