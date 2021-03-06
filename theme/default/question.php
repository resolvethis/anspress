<?php
/**
 * This file is responsible for displaying question page
 * This file can be overridden by creating a anspress directory in active theme folder.
 *
 * @package    AnsPress
 * @license    http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author    Rahul Aryan <rah12@live.com>
 */

global $post;
?>
<div id="ap-single" class="clearfix" itemtype="http://schema.org/Question" itemscope="">	
	<div class="ap-question-lr">		
		<div class="col-md-2">
			<div class="ap-question-info">
				<?php
					the_widget( 'AnsPress_Subscribe_Widget', 'title=');
					the_widget( 'AnsPress_Participants_Widget', 'title=&avatar_size=25');
					/**
					 * ACTION: ap_question_info
					 * @since 	2.0.0-alpha2
					 */
					do_action('ap_question_info');
					the_widget( 'AnsPress_Stats_Widget', 'title='.__('Stats', 'ap'), 'before_title=<h3 class="ap-widget-title">&after_title=</h3>');
				?>
			</div>
		</div>
		<div class="ap-question-left col-md-7">
			<div id="question" role="main" class="ap-content question" data-id="<?php echo get_the_ID(); ?>">
				<header class="ap-q-head">
					<?php 
						/**
						 * ACTION: ap_before_question_title
						 * @since 	2.0
						 */
						do_action('ap_before_question_title', $post);
					?>
					<?php 
						/**
						 * By default this title is hidden
						 */
						if(ap_opt('show_title_in_question')) : 
					?>
						<h1 class="entry-title"><a href="<?php get_permalink() ?>"><?php the_title(); ?></a></h1>
					<?php endif; ?>

					<?php 
						/**
						 * ACTION: ap_after_question_title
						 * @since 	2.0
						 */
						do_action('ap_after_question_title', $post);
					?>
				</header>
				<div class="ap-question-cells clearfix">
					<div class="ap-question-metas clearfix">
						<div class="ap-avatar ap-pull-left">
							<a href="<?php echo ap_user_link(get_the_author_meta('ID'))?>">
								<?php echo get_avatar( get_the_author_meta( 'user_email' ), ap_opt('avatar_size_qquestion') ); ?>
							</a>						
						</div>
						<div class="no-overflow">
							<?php ap_user_display_meta(true, false, true); ?>

							<!-- TODO: Show all questions history on toggle -->
							<ul class="ap-display-question-meta ap-ul-inline">
								<?php echo ap_display_question_metas() ?>
							</ul>
						</div>
					</div>
					
					<!-- Start ap-content-inner -->
					<div class="ap-content-inner">
				
						

						<div class="question-content ap-post-content" itemprop="text">
							<?php the_content(); ?>									
						</div>
						<?php 
							/**
							 * ACTION: ap_after_question_content
							 * @since 	2.0
							 */
							do_action('ap_after_question_content', $post);
						?>

						
						<?php ap_post_actions_buttons() ?>

						<?php 
							/**
							 * ACTION: ap_after_question_actions
							 * @since 	2.0
							 */
							global $post;
							do_action('ap_after_question_actions', $post);
						?>

						<?php if(ap_opt('show_comments_by_default')) comments_template(); ?>

					</div>
					<!-- End ap-content-inner -->					
				</div>		
			</div>
			
			<?php

				if(ap_have_ans(get_the_ID())){
					$ans_count = ap_count_answer_meta();
					echo '<div class="ap-sorting-tab clearfix">';
						echo '<h3 class="ap-widget-title ap-pull-left">'. sprintf(__('%s answers', 'ap'), '<span data-view="answer_count">'.$ans_count.'</span>') .'</h3>';
						ap_answers_tab();
					echo '</div>';
					include(ap_get_theme_location('best_answer.php'));
					ap_get_answers();
				} 
			?>
				
			<?php 				
				include(ap_get_theme_location('answer-form.php')); 
			?>
		</div>

		<div class="ap-question-right col-md-3">
			<?php dynamic_sidebar( 'ap-question-sidebar' ); ?>
		</div>
	</div>
</div>
