/*!
 * パスワード保護フォームの送信を REST で行い、エラーをフォーム上に表示する
 *
 * フォームは JS がなくても動く（エンドポイントがリダイレクトで応える）。
 * この JS は、パスワードが違うときに遷移せずにエラーを出すための上乗せ。
 *
 * @handle pubpla-gatekeeper-post-password
 * @deps jquery, wp-api-fetch
 */

jQuery( document ).ready( function( $ ) {
	'use strict';

	$( '.post-password-form' ).on( 'submit', function( e ) {
		e.preventDefault();

		const $form = $( this );
		const $submitButton = $form.find( 'input[type="submit"]' );
		const $passwordInput = $form.find( 'input[name="post_password"]' );

		$submitButton.prop( 'disabled', true );
		$form.find( '.password-error' ).remove();

		wp.apiFetch( {
			url: $form.attr( 'action' ),
			method: 'POST',
			data: {
				post_password: $passwordInput.val(),
			},
		} ).then( function( response ) {
			window.location.href = response.url;
		} ).catch( function( error ) {
			$submitButton.prop( 'disabled', false );
			// サーバーのメッセージは HTML として解釈させない。
			$( '<div class="password-error" role="alert"></div>' )
				.css( { color: 'red', marginTop: '10px' } )
				.text( error.message || 'パスワードの認証に失敗しました。' )
				.appendTo( $form );
		} );
	} );
} );
