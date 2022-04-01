<?php

declare(strict_types=1);

namespace ContaoBootstrap\Grid\Listener\Dca;

use Contao\Database\Result;
use Contao\DataContainer;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Model;

use function array_unshift;
use function assert;
use function sprintf;
use function time;

/**
 * Data container helper class for form.
 */
class FormListener extends AbstractWrapperDcaListener
{
    /**
     * Generate the columns.
     *
     * @param int           $value         Number of columns which should be generated.
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function generateColumns($value, $dataContainer)
    {
        if (! $dataContainer->activeRecord) {
            return null;
        }

        $current = $dataContainer->activeRecord;
        assert($current instanceof FormModel || $current instanceof Result);

        if ($value && $dataContainer->activeRecord) {
            $stopElement  = $this->getStopElement($current);
            $nextElements = $this->getNextElements($stopElement);
            $sorting      = $stopElement->sorting;

            $sorting = $this->createSeparators($value, $current, $sorting);

            array_unshift($nextElements, $stopElement);
            $this->updateSortings($nextElements, $sorting);
        }

        return null;
    }

    /**
     * Get the next content elements.
     *
     * @param FormFieldModel $current Current content model.
     *
     * @return FormFieldModel[]
     */
    protected function getNextElements($current): array
    {
        $collection = FormFieldModel::findBy(
            [
                'tl_form_field.pid=?',
                '(tl_form_field.type != ? AND tl_form_field.bs_grid_parent = ?)',
                'tl_form_field.sorting > ?',
            ],
            [$current->pid, 'bs_gridStop', $current->id, $current->sorting],
            ['order' => 'tl_form_field.sorting ASC']
        );

        if ($collection) {
            return $collection->getIterator()->getArrayCopy();
        }

        return [];
    }

    /**
     * Get related stop element.
     *
     * @param FormFieldModel|Result $current Current element.
     *
     * @return FormFieldModel|Model
     */
    protected function getStopElement($current): Model
    {
        $stopElement = FormFieldModel::findOneBy(
            ['tl_form_field.type=?', 'tl_form_field.bs_grid_parent=?'],
            ['bs_gridStop', $current->id]
        );

        if ($stopElement) {
            return $stopElement;
        }

        $nextElements = $this->getNextElements($current);
        $stopElement  = $this->createStopElement($current, $current->sorting);
        $this->updateSortings($nextElements, $stopElement->sorting);

        return $stopElement;
    }

    /**
     * Create a grid element.
     *
     * @param FormFieldModel $current Current content model.
     * @param string         $type    Type of the content model.
     * @param int            $sorting The sorting value.
     *
     * @return FormFieldModel|Model
     */
    protected function createGridElement($current, string $type, int &$sorting): Model
    {
        $model                 = new FormFieldModel();
        $model->tstamp         = time();
        $model->pid            = $current->pid;
        $model->sorting        = $sorting;
        $model->type           = $type;
        $model->bs_grid_parent = $current->id;
        $model->save();

        return $model;
    }

    /**
     * Get all grid parent options.
     *
     * @return array<int|string,string>
     */
    public function getGridParentOptions(): array
    {
        $columns[] = 'tl_form_field.type = ?';
        $columns[] = 'tl_form_field.pid = ?';

        $values[] = 'bs_gridStart';
        $values[] = CURRENT_ID;

        $collection = FormFieldModel::findBy($columns, $values);
        $options    = [];

        if ($collection) {
            foreach ($collection as $model) {
                $options[$model->id] = sprintf(
                    '%s [%s]',
                    $model->bs_grid_name,
                    $model->getRelated('bs_grid')->title
                );
            }
        }

        return $options;
    }
}
