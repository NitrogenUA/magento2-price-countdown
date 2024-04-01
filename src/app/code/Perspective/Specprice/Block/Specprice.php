<?php

namespace Perspective\Specprice\Block;

use Magento\Catalog\Model\Product;

class Specprice extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Product
     */
    protected $_product = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Locale Date/Timezone
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $_timezone;

    protected $specialPriceEndDate;

    public $priceRule = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param RuleFactory $ruleFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogRule\Model\RuleFactory $ruleFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_ruleFactory = $ruleFactory;
        $this->_timezone = $context->getLocaleDate();
        parent::__construct($context, $data);
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    private function dateToObj($date)
    {
        if (!isset($date)) {
            return null;
        }

        $timezone = new \DateTimeZone($this->_timezone->getConfigTimezone());
        // Format given date according to store timezone
        $date = new \DateTime($date, $timezone);
        // Check if given time is ahead of current time
        return $this->_timezone->date()->getTimestamp() < $date->getTimestamp() ? $date : null;
    }

    public function getSpecialPriceEndDate()
    {
        // Retrive special price for current product
        $specialPrice = $this->getProduct()->getSpecialPrice();

        // If product special price is set retrieve special price end date
        if ($specialPrice !== null && $specialPrice !== false) {
            $toDate = $this->dateToObj($this->_product->getSpecialToDate());
        }

        // If product special price end date was retrieved store it
        $this->specialPriceEndDate = $toDate ?? null;
        if (isset($this->specialPriceEndDate)) $this->priceRule = "Product special price";

        // Retrive a collection of all active catalog price rules
        $catalogRules = $this->_ruleFactory
                            ->create()
                            ->getCollection()
                            ->addIsActiveFilter();

        // Check if any of the catalog price rules match current product
        foreach ($catalogRules as $rule) {
            $matchingProducts = $rule->getMatchingProductIds();

            if (array_key_exists($this->_product->getId(), $matchingProducts)) {
                // If catalog price rule matches current product retrieve catalog price rule end date
                $ruleToDate = $this->dateToObj($rule->getToDate());
                
                if (isset($ruleToDate)) {
                    $ruleTitle = $rule->getDescription() ? $rule->getDescription() : $rule->getName();
                    if (isset($this->specialPriceEndDate)) {
                        // If catalog price rule end date was retrieved compare it to product special price end date
                        if ($ruleToDate->getTimestamp() < $this->specialPriceEndDate->getTimestamp()) {
                            $this->specialPriceEndDate = $ruleToDate;
                            $this->priceRule = sprintf("Catalog price rule: %s", $ruleTitle);
                        }
                    } else {
                        $this->specialPriceEndDate = $ruleToDate;
                        $this->priceRule = sprintf("Catalog price rule: %s", $ruleTitle);
                    }
                }
            }
        }
        
        return $this->specialPriceEndDate;
    }
}
?>