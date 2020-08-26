<?php

namespace Neww\Test\Cron;

class Test1
{

    protected $logger;
    protected $ruleFactory;
    protected $productRuleFactory;
    protected $foundProductRuleFactory;
    protected $ruleResource;

    public function __construct(\Psr\Log\LoggerInterface $logger,
    \Magento\Framework\Filesystem\Driver\File $fileDriver,
    \Magento\Framework\File\Csv $csvParser,
    \Magento\SalesRule\Model\RuleFactory $ruleFactory,
    \Magento\SalesRule\Model\Rule\Condition\ProductFactory $productRuleFactory,
    \Magento\SalesRule\Model\Rule\Condition\Product\FoundFactory $foundProductRuleFactory,
    \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource)
    {
        $this->logger = $logger;
        $this->fileDriver = $fileDriver;
        $this->csvParser = $csvParser;
        $this->rulesFactory = $ruleFactory;
        $this->productRuleFactory = $productRuleFactory;
        $this->foundProductRuleFactory = $foundProductRuleFactory;
        $this->ruleResource = $ruleResource;
    }

	public function execute()
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cron223.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info(__METHOD__);
        $file = '/home/team_magento/public_html/magento2/mage/var/import/promo_rules.csv';
        $rowcount = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
            $header = fgetcsv($handle, $max_line_length);
            $header_colcount = count($header);
            while (($row = fgetcsv($handle, $max_line_length)) !== FALSE) {
                $row_colcount = count($row);
                if ($row_colcount == $header_colcount) {
                    $entry = array_combine($header, $row);
                    $csv[] = $entry;
                } else {
                    error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                    return null;
                }
                $rowcount++;
            }
            fclose($handle);
        } else {
            error_log("csvreader: Could not read CSV \"$file\"");
            return null;
        }
        $csvData = array();
        foreach ($csv as $data) {
            $csvData = $data;
            $sku = "HISH12-32-Red-1";
            $shoppingCartPriceRule = $this->rulesFactory->create();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
            $grandTotal = $cart->getQuote()->getSubtotal();
            $grandTotal = $cart->getQuote()->getGrandTotal();

                $shoppingCartPriceRule->setName($csvData['Name'])
                    ->setDescription('Buy one item at regular price, and receive a second item for just $1.00 more!')
                    ->setFromDate('2000-01-01')
                    ->setToDate(NULL)
                    ->setUsesPerCustomer('0')
                    ->setCustomerGroupIds(array('0', '1', '2', '3',))
                    ->setIsActive('1')
                    ->setStopRulesProcessing('0')
                    ->setIsAdvanced('1')
                    ->setProductIds(NULL)
                    ->setSortOrder('1')
                    ->setSimpleAction('by_percent')
                    ->setDiscountAmount($csvData['Discount'])
                    ->setDiscountQty(NULL)
                    ->setDiscountStep('0')
                    ->setSimpleFreeShipping('0')
                    ->setApplyToShipping('0')
                    ->setTimesUsed('0')
                    ->setIsRss('0')
                    ->setWebsiteIds(array('1',))
                    ->setCouponType('2')
                    ->setCouponCode($csvData['Code'])
                    ->setUsesPerCoupon(NULL);

                $item_found = $this->foundProductRuleFactory->create()
                    ->setType('Magento\SalesRule\Model\Rule\Condition\Product\Found')
                    ->setValue(1) // 1 == FOUND
                    ->setAggregator('all'); // match ALL conditions
                $shoppingCartPriceRule->getConditions()->addCondition($item_found);
                $qtyCond = $this->productRuleFactory->create()
                    ->setType('Magento\SalesRule\Model\Rule\Condition\Product')
                    ->setData('attribute','quote_item_price')
                    ->setData('operator','(>=)')
                    ->setValue('200');
                $shoppingCartPriceRule->getActions()->addCondition($qtyCond);
                // print_r($shoppingCartPriceRule->getData()); exit("Vfgvrvt");
                $this->ruleResource->save($shoppingCartPriceRule);
        }
		return $this;
	}
}