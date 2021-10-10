<?php
/**
 * @brief		billingAgreements Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Dec 2015
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * billingAgreements Task
 */
class _billingAgreements extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_next_cycle<?', time() ), 'ba_next_cycle ASC', 50 ), 'IPS\nexus\Customer\BillingAgreement' ) as $billingAgreement )
		{
			try
			{
				/* Get the status? */
				if ( $billingAgreement->status() != $billingAgreement::STATUS_ACTIVE )
				{
					if ( $billingAgreement->status() == $billingAgreement::STATUS_CANCELED )
					{
						$billingAgreement->canceled = TRUE;
					}
					throw new \DomainException("{$billingAgreement->id} - {$billingAgreement->status()}");
				}
				
				/* Get the term */
				$term = $billingAgreement->term();
				
				/* Fetch the latest unclaimed transaction */
				try
				{
					$transaction = $billingAgreement->latestUnclaimedTransaction();
				}
				/* If there isn't one yet, it might just be that PayPal hasn't taken it yet. Let it try for up to 5 days before giving up, which is how long PayPal will try for if there's an issue. */
				catch ( \OutOfRangeException $e )
				{
					if ( $billingAgreement->next_cycle->getTimestamp() > ( time() - ( 86400 * 5 ) ) )
					{
						continue;
					}
					else
					{
						throw $e;
					}
				}
				
				/* Get purchases */
				$purchases = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_billing_agreement=?', $billingAgreement->id ) ), 'IPS\nexus\Purchase' );
				
				/* Generate an invoice */
				$invoice = new \IPS\nexus\Invoice;
				$invoice->system = TRUE;
				$invoice->currency = $transaction->amount->currency;
				$invoice->member = $billingAgreement->member;
				foreach ( $purchases as $purchase )
				{
					$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase ) );
				}
				$invoice->save();
				
				/* Assign the transaction to it */
				$transaction->invoice = $invoice;
				$transaction->save();
				$transaction->approve();
				$invoice->status = $transaction->invoice->status;
				
				/* Log */
				$invoice->member->log( 'transaction', array(
					'type' => 'paid',
					'status' => \IPS\nexus\Transaction::STATUS_PAID,
					'id' => $transaction->id,
					'invoice_id' => $invoice->id,
					'invoice_title' => $invoice->title,
					'automatic' => TRUE,
				), FALSE );
				
				/* Update the purchase */
				if ( $invoice->status !== $invoice::STATUS_PAID )
				{
					foreach ( $purchases as $purchase )
					{
						$purchase->invoice_pending = $invoice;
						$purchase->save();
					}
				}
			
				/* Send notification */
				$invoice->sendNotification();
				
				/* Update billing agreement */
				if ( $invoice->status === $invoice::STATUS_PAID )
				{
					$billingAgreement->next_cycle = $billingAgreement->nextPaymentDate();
				}
				else
				{
					$billingAgreement->next_cycle = NULL;
				}
				$billingAgreement->save();
			}
			catch ( \IPS\Http\Url\Exception $e )
			{
				/* Just log the issue, but don't do anything here,.. this was probably a temporary issue so let the next call run this again */
				\IPS\Log::log( $e, 'ba_sync_fail' );
			}
			catch ( \Exception $e )
			{
				\IPS\Log::log( $e, 'ba_sync_fail' );
				
				$billingAgreement->next_cycle = NULL;
				$billingAgreement->save();
			}
			catch ( \Throwable $e )
			{
				\IPS\Log::log( $e, 'ba_sync_fail' );
				
				$billingAgreement->next_cycle = NULL;
				$billingAgreement->save();
			}
		}
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}