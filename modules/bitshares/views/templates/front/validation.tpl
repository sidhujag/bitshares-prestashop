{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Go back to the Checkout' mod='bitshares'}">{l s='Checkout' mod='bitshares'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Bitshares payment' mod='bitshares'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='bitshares'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Bitshares payment' mod='bitshares'}</h3>

<form action="{$link->getModuleLink('bitshares', 'validation', [], true)|escape:'html'}" method="post">
	<input type="hidden" name="confirm" value="1" />
	<p>
		<img src="{$this_path}logo.gif" alt="{l s='Bitshares payment' mod='bitshares'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen Bitshares.' mod='bitshares'}
		<br/><br />
		{l s='The total amount of your order is' mod='bitshares'}
		<span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}</span>
		{if $use_taxes == 1}
		    {l s='(tax incl.)' mod='bitshares'}
		{/if}
	</p>
	<p>
		<br /><br />
		<br /><br />
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'.' mod='bitshares'}</b>
	</p>
	<p class="cart_navigation" id="cart_navigation">
		<a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='Other payment methods' mod='bitshares'}</a>
		<input type="submit" value="{l s='I confirm my order' mod='bitshares'}" class="exclusive_large" />
	</p>
</form>
