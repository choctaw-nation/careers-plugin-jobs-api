import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function useOptions( nonce: string, restUrl: string ) {
	const [ settings, setSettings ] = useState< Settings >( {} as Settings );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notices, setNotices ] = useState<
		{
			content: string;
			politeness?: 'polite' | 'assertive';
			id: string;
			explicitDismiss: boolean;
		}[]
	>( [] );

	// Configure apiFetch nonce once on mount
	useEffect( () => {
		apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	}, [ nonce ] );

	// Load saved settings from REST API on mount
	useEffect( () => {
		apiFetch( { path: restUrl } )
			.then( ( data: Settings ) => {
				setSettings( data );
			} )
			.catch( () => {
				setNotices( ( prev ) => [
					...prev,
					{
						content:
							'Failed to load settings. Please refresh the page.',
						politeness: 'assertive',
						id: Math.random().toString( 36 ).slice( 2, 9 ),
						explicitDismiss: false,
					},
				] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ nonce, restUrl ] );

	// Validate and submit updated settings to REST API
	async function handleSubmit( e: React.FormEvent ) {
		e.preventDefault();
		setIsSaving( true );
		let validationFailed = false;
		Object.entries( settings ).forEach( ( [ key, value ] ) => {
			if ( validationFailed ) {
				return;
			}
			if ( ! value ) {
				validationFailed = true;
				setNotices( ( prev ) => [
					...prev,
					{
						content: `${ key } is required!`,
						politeness: 'assertive',
						id: Math.random().toString( 36 ).slice( 2, 9 ),
						explicitDismiss: false,
					},
				] );
				setIsSaving( false );
			}
		} );
		if ( validationFailed ) {
			return;
		}
		try {
			await apiFetch( {
				path: restUrl,
				method: 'POST',
				data: {
					...settings,
					syncTime: `${ settings.syncTime.hours
						.toString()
						.padStart( 2, '0' ) }:${ settings.syncTime.minutes
						.toString()
						.padStart( 2, '0' ) }`,
				},
			} );
			setNotices( ( prev ) => [
				...prev,
				{
					content: 'Settings saved successfully.',
					explicitDismiss: false,
					id: Math.random().toString( 36 ).slice( 2, 9 ),
				},
			] );
		} catch {
			setNotices( ( prev ) => [
				...prev,
				{
					content: 'Failed to save settings. Please try again.',
					politeness: 'assertive',
					explicitDismiss: false,
					id: Math.random().toString( 36 ).slice( 2, 9 ),
				},
			] );
		} finally {
			setIsSaving( false );
		}
	}

	/** Update a single credential field for the given environment */
	const updateField = useCallback(
		( field: keyof Settings, value: string | TimeInputValue ) => {
			setSettings( ( prev ) => ( {
				...prev,
				[ field ]: value,
			} ) );
		},
		[]
	);
	return {
		handleSubmit,
		updateField,
		settings,
		notices,
		setNotices,
		isSaving,
		isLoading,
	};
}
