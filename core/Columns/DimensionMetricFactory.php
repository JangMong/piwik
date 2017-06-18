<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Columns;

use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\ComputedMetric;
use Piwik\Plugin\Report;

class DimensionMetricFactory
{
    /**
     * @var Dimension
     */
    private $dimension = null;

    /**
     * Generates a new report widget factory.
     * @param Report $report  A report instance, widgets will be created based on the data provided by this report.
     */
    public function __construct(Dimension $dimension)
    {
        $this->dimension = $dimension;
    }

    /**
     * @return ArchivedMetric
     */
    public function createCustomMetric($metricName, $readableName, $aggregation, $documentation = '')
    {
        if (!$this->dimension->getDbTableName() || !$this->dimension->getColumnName()) {
            throw new \Exception(sprintf('Cannot make metric from dimension %s because DB table or column missing', $this->dimension->getId()));
        }

        $metric = new ArchivedMetric($this->dimension->getDbTableName(), $this->dimension->getColumnName(), $aggregation);
        $metric->setType($this->dimension->getType());
        $metric->setName($metricName);
        $metric->setTranslatedName($readableName);
        $metric->setDocumentation($documentation);
        $metric->setCategory($this->dimension->getCategory());
        $metric->setDimension($this->dimension);

        return $metric;
    }

    /**
     * @return \Piwik\Plugin\ComputedMetric
     */
    public function createComputedMetric($metricName1, $metricName2, $aggregation)
    {
        if ($aggregation === ComputedMetric::AGGREGATION_AVG) {
            $name = 'avg_' . $metricName1 . '_per_' . $metricName2;
            $translatedName = '';
            $documentation = 'Average value of ' . $this->dimension->getName() . ' per ' . $metricName2;
        } elseif ($aggregation === ComputedMetric::AGGREGATION_RATE) {
            $name = $this->dimension->getMetricId() . '_rate';
            $translatedName = $this->dimension->getName() . ' Rate';
            $documentation = 'The percentage of ' . $this->dimension->getNamePlural();
        } else {
            throw new \Exception('Not supported aggregation type');
        }

        $name = str_replace(array('nb_uniq_', 'uniq_', 'nb_', 'sum_', 'max_', 'min_', '_rate', '_count'), '', $name);

        $metric = new ComputedMetric($metricName1, $metricName2, $aggregation);
        if ($aggregation === ComputedMetric::AGGREGATION_RATE) {
            $metric->setType(Dimension::TYPE_PERCENT);
        } else {
            $metric->setType($this->dimension->getType());
        }
        $metric->setName($name);
        $metric->setTranslatedName($translatedName);
        $metric->setDocumentation($documentation);
        $metric->setCategory($this->dimension->getCategory());
        return $metric;
    }

    /**
     * @return ArchivedMetric
     */
    public function createMetric($aggregation)
    {
        $dimension = $this->dimension;

        if (!$dimension->getNamePlural()) {
            throw new \Exception(sprintf('No metric can be created for this dimension %s automatically because no $namePlural is set.', $dimension->getId()));
        }

        $prefix = '';
        $translatedName = $dimension->getNamePlural();

        $documentation = '';

        switch ($aggregation) {
            case ArchivedMetric::AGGREGATION_COUNT;
                $prefix = 'nb_';
                $translatedName = $dimension->getNamePlural();
                $documentation = 'The number of ' . $dimension->getNamePlural();
                break;
            case ArchivedMetric::AGGREGATION_SUM;
                $prefix = 'sum_';
                $translatedName = 'Total ' . $dimension->getNamePlural();
                $documentation = 'The total number of ' . $dimension->getNamePlural();
                break;
            case ArchivedMetric::AGGREGATION_MAX;
                $prefix = 'max_';
                $translatedName = 'Max ' . $dimension->getNamePlural();
                $documentation = 'The maximum value of ' . $dimension->getNamePlural();
                break;
            case ArchivedMetric::AGGREGATION_MIN;
                $prefix = 'min_';
                $translatedName = 'Min ' . $dimension->getNamePlural();
                $documentation = 'The minimum value of ' . $dimension->getNamePlural();
                break;
            case ArchivedMetric::AGGREGATION_UNIQUE;
                $prefix = 'nb_uniq_';
                $translatedName = 'Unique ' . $dimension->getNamePlural();
                $documentation = 'Unique ' . $dimension->getNamePlural();
                break;
        }

        return $this->createCustomMetric($prefix . $dimension->getMetricId(), $translatedName, $aggregation, $documentation);
    }
}