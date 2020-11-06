<?php
class Customdiscount_Setdiscount_Model_Price_Observer
{
    function set_product_discount($observer)
    {
        $quote = $observer->getEvent()
            ->getQuote();
        $quoteid = $quote->getId();

        $cheapest_product = NULL;
        $cart_items = $quote->getAllItems();
        $num_items = count($cart_items);

        if ($num_items > 1)
        {
            $cheapest_product_price = $cart_items[0]->getPrice();

            foreach ($cart_items as $item)
            {
                $item_price[] = $item->getPrice();
                if ($item->getPrice() < $cheapest_product_price) $cheapest_product_price = $item->getPrice();
            }

            $discountAmount = $cheapest_product_price / 2;

            if ($quoteid)
            {
                if ($discountAmount > 0)
                {
                    $total = $quote->getBaseSubtotal();
                    $quote->setSubtotal(0);
                    $quote->setBaseSubtotal(0);

                    $quote->setSubtotalWithDiscount(0);
                    $quote->setBaseSubtotalWithDiscount(0);

                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);

                    $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
                    foreach ($quote->getAllAddresses() as $address)
                    {

                        $address->setSubtotal(0);
                        $address->setBaseSubtotal(0);

                        $address->setGrandTotal(0);
                        $address->setBaseGrandTotal(0);

                        $address->collectTotals();

                        $quote->setSubtotal((float)$quote->getSubtotal() + $address->getSubtotal());
                        $quote->setBaseSubtotal((float)$quote->getBaseSubtotal() + $address->getBaseSubtotal());

                        $quote->setSubtotalWithDiscount((float)$quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount());
                        $quote->setBaseSubtotalWithDiscount((float)$quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount());

                        $quote->setGrandTotal((float)$quote->getGrandTotal() + $address->getGrandTotal());
                        $quote->setBaseGrandTotal((float)$quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

                        $quote->save();

                        $quote->setGrandTotal($quote->getBaseSubtotal() - $discountAmount)->setBaseGrandTotal($quote->getBaseSubtotal() - $discountAmount)->setSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)->setBaseSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)->save();

                        if ($address->getAddressType() == $canAddItems)
                        {
                           
                            $address->setSubtotalWithDiscount((float)$address->getSubtotalWithDiscount() - $discountAmount);
                            $address->setGrandTotal((float)$address->getGrandTotal() - $discountAmount);
                            $address->setBaseSubtotalWithDiscount((float)$address->getBaseSubtotalWithDiscount() - $discountAmount);
                            $address->setBaseGrandTotal((float)$address->getBaseGrandTotal() - $discountAmount);
                            if ($address->getDiscountDescription())
                            {
                                $address->setDiscountAmount(-($address->getDiscountAmount() - $discountAmount));
                                $address->setDiscountDescription($address->getDiscountDescription() . ', Custom Discount');
                                $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount() - $discountAmount));
                            }
                            else
                            {
                                $address->setDiscountAmount(-($discountAmount));
                               
                                $address->setBaseDiscountAmount(-($discountAmount));
                            }
                            $address->save();
                        } 
                    } 
                }
            }
        }
    }
}

