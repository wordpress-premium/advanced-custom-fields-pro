/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n'
import { addFilter } from '@wordpress/hooks'

addFilter( 'rank_math_content_ai_help_text', 'rank-math-pro', () => {
	return __( 'Contact your SEO service provider for more AI credits.', 'rank-math-pro' )
} )

addFilter( 'rank_math_content_ai_credits_notice', 'rank-math-pro', () => {
	return __( 'You have used all of your AI credits and need to purchase more from your SEO service provider.', 'rank-math-pro' )
} )
