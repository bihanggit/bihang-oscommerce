bihang/oscommerce-plugin
========================

# Installation

1. Copy `bihang_callback.php` into your osCommerce catalog directory
2. Copy the bihang directory into your osCommerce catalog directory
3. Copy `includes/modules/payment/bihang.php` into `catalog/includes/modules/payment/`
4. Copy `includes/languages/english/modules/payment/bihang.php` into `catalog/includes/languages/english/modules/payment/`

# Configuration

1. Create an API key and secrect at bihang.com 
2. In your osCommerce admin panel under Modules > Payment, install the "Bitcoin via bihang" module
3. Fill out all of the configuration information:
	- Verify that the module is enabled.
	- Copy/Paste the API key and secret you created in step 1 into the API Key field
	- Choose a status for unpaid and paid orders (or leave the default values as
      defined).
	- Verify that the currencies displayed corresponds to what you want and to
      those accepted by oklik.com (BTC USD CNY).
	- Choose a sort order for displaying this payment option to visitors.
      Lowest is displayed first.

# Usage

When a user chooses the "Bitcoin via bihang" payment method, they will be
presented with an order summary as the next step (prices are shown in whatever
currency they've selected for shopping). Upon confirming their order, the system
takes the user to bihang.com.  Once payment is received, a link is presented
to the shopper that will take them back to your website.

In your Admin control panel, you can see the orders made via Bitcoins just as
you could see for any other payment mode.  The status you selected in the
configuration steps above will indicate whether the order has been paid for.  

Note: This extension does not provide a means of automatically pulling a
current BTC exchange rate for presenting BTC prices to shoppers.

