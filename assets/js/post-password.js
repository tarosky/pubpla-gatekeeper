/**
 * パスワード保護機能をサポートするJS
 *
 * @handle taro-post-password
 * @deps jquery, wp-api-fetch
 */

jQuery(document).ready(function($) {
	'use strict';

	/**
	 * パスワード保護フォームの送信を処理
	 */
	$('.post-password-form').on('submit', function(e) {
		e.preventDefault();
		
		const $form = $(this);
		const $submitButton = $form.find('input[type="submit"]');
		const $passwordInput = $form.find('input[name="post_password"]');
		const actionUrl = $form.attr('action');
		
		// ボタンを無効化
		$submitButton.prop('disabled', true);
		
		// エラーメッセージがあれば削除
		$form.find('.password-error').remove();
		
		// APIリクエストの実行
		wp.apiFetch({
			url: actionUrl,
			method: 'POST',
			data: {
				post_password: $passwordInput.val(),
			},
		}).then(function(response) {
			// 成功時はページをリダイレクト
			window.location.href = response.url;
		}).catch(function(error) {
			// エラー時はボタンを再度有効化
			$submitButton.prop('disabled', false);
			
			// エラーメッセージを表示
			let errorMessage = 'パスワードの認証に失敗しました。';
			if (error.message) {
				errorMessage = error.message;
			}
			
			$form.append('<div class="password-error" style="color: red; margin-top: 10px;">' + errorMessage + '</div>');
		});
	});
});
