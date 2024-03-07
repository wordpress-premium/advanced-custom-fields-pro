<?php
/**
 * BBPress module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Traits\Hooker;
use RankMath\Traits\Ajax;
use RankMath\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * BBPress class.
 *
 * @codeCoverageIgnore
 */
class BBPress {

	use Hooker, Ajax;

	/**
	 * Post meta key for solved answers.
	 *
	 * @var string
	 */
	public $meta_key = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->meta_key = 'rank_math_bbpress_solved_answer';
		$this->action( 'wp', 'hooks' );
		$this->ajax( 'mark_answer_solved', 'mark_answer_solved' );
		$this->action( 'rank_math/json_ld', 'add_qa_schema', 99 );
	}

	/**
	 * Init hooks.
	 */
	public function hooks() {
		if ( ! is_singular( 'topic' ) || ! current_user_can( 'moderate', get_the_ID() ) ) {
			return;
		}

		$this->action( 'bbp_get_reply_content', 'add_solved_answer_button', 9, 2 );
		$this->action( 'wp_enqueue_scripts', 'enqueue' );
		$this->action( 'wp_footer', 'add_css' );
	}

	/**
	 * Enqueue Script required by plugin.
	 */
	public function enqueue() {
		wp_enqueue_script( 'rank-math-bbpress', RANK_MATH_PRO_URL . 'includes/modules/bbPress/assets/js/bbpress.js', [ 'jquery' ], RANK_MATH_PRO_VERSION, true );
	}

	/**
	 * Add Mark Reply as Solved button to the Reply content.
	 *
	 * @param string $content  Original reply content.
	 * @param int    $reply_id Reply ID.
	 *
	 * @return string $content New content.
	 */
	public function add_solved_answer_button( $content, $reply_id ) {
		$reply = bbp_get_reply( $reply_id );
		if ( empty( $reply ) ) {
			return $content;
		}

		$topic_id      = bbp_get_reply_topic_id();
		$answered      = (int) get_post_meta( $topic_id, $this->meta_key, true );
		$solved_text   = __( 'Mark Unsolved.', 'rank-math-pro' );
		$unsolved_text = __( 'Mark Solved.', 'rank-math-pro' );
		$is_solved     = $reply_id === $answered;
		$class         = metadata_exists( 'post', $topic_id, $this->meta_key ) && ! $is_solved ? 'rank-math-hidden' : '';
		$text          = $is_solved ? $solved_text : $unsolved_text;

		$content .= '
		<div class="rank-math-mark-solved ' . esc_attr( $class ) . '">
			<a
				href="#"
				data-id="' . esc_attr( $reply_id ) . '"
				data-topic-id="' . esc_attr( $topic_id ) . '"
				data-solved-text="' . esc_attr( $solved_text ) . '"
				data-unsolved-text="' . esc_attr( $unsolved_text ) . '"
				data-is-solved="' . $is_solved . '">'
			. apply_filters( 'rank_math/bbpress/solved_text', $text, $is_solved )
			. '</a>
		</div>';

		return $content;
	}

	/**
	 * AJAX function to mark answer as solved.
	 */
	public function mark_answer_solved() {
		check_ajax_referer( 'rank-math-ajax-nonce', 'security' );
		$topic = Param::post( 'topic' );
		if ( ! current_user_can( 'moderate', $topic ) ) {
			return false;
		}

		$is_solved = Param::post( 'isSolved' );
		if ( $is_solved ) {
			return delete_post_meta( $topic, $this->meta_key );
		}

		$reply = Param::post( 'reply' );
		return update_post_meta( $topic, $this->meta_key, $reply );
	}

	/**
	 * Add QA Schema Data.
	 *
	 * @param  array $data Array of json-ld data.
	 * @return array
	 */
	public function add_qa_schema( $data ) {
		if ( ! is_singular( 'topic' ) ) {
			return $data;
		}

		global $post;
		$approved_answer = get_post_meta( $post->ID, $this->meta_key, true );
		if ( ! $approved_answer ) {
			return $data;
		}

		$data[] = [
			'@type'      => 'QAPage',
			'mainEntity' => [
				'@type'          => 'Question',
				'name'           => get_the_title( $post ),
				'text'           => get_the_excerpt( $post ),
				'dateCreated'    => get_post_time( 'Y-m-d\TH:i:sP', false ),
				'answerCount'    => get_post_meta( $post->ID, '_bbp_reply_count', true ),
				'author'         => [
					'@type' => 'Person',
					'name'  => get_the_author(),
				],
				'acceptedAnswer' => [
					'@type'       => 'Answer',
					'text'        => get_post_field( 'post_content', $approved_answer ),
					'dateCreated' => get_post_time( 'Y-m-d\TH:i:sP', false, $approved_answer ),
					'url'         => bbp_get_reply_url( $approved_answer ),
					'author'      => [
						'@type' => 'Person',
						'name'  => bbp_get_reply_author( $approved_answer ),
					],
				],
			],
		];

		return $data;
	}

	/**
	 * Add CSS inline, once.
	 */
	public function add_css() {
		?>
		<style type="text/css">
			.rank-math-mark-solved  {
				text-align: right;
			}
			.rank-math-hidden {
				display: none;
			}
		</style>
		<?php
	}
}
