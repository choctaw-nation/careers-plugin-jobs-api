import {
	Panel,
	PanelBody,
	TextControl,
	RadioControl,
	Button,
	SnackbarList,
	Spinner,
	TimePicker,
} from '@wordpress/components';
import useOptions from './useOptions';

interface AppProps {
	nonce: string;
	restUrl: string;
}

export default function App( { nonce, restUrl }: AppProps ) {
	const {
		updateField,
		isSaving,
		notices,
		isLoading,
		settings,
		handleSubmit,
		setNotices,
	} = useOptions( nonce, restUrl );
	if ( isLoading ) {
		return (
			<div
				style={ {
					display: 'flex',
					justifyContent: 'center',
					alignItems: 'center',
					padding: '2rem',
				} }
			>
				<h2>Loading API Settings...</h2>
				<Spinner />
			</div>
		);
	}
	return (
		<form
			onSubmit={ handleSubmit }
			style={ {
				display: 'flex',
				flexDirection: 'column',
				gap: '1.5rem',
			} }
		>
			<Panel>
				<PanelBody title="Jobs API Credentials">
					<p>These credentials are not to be shared!</p>
					<div
						style={ {
							display: 'flex',
							flexDirection: 'column',
							alignItems: 'stretch',
							gap: '1rem',
						} }
					>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label="API Endpoint"
							value={ settings.jobsEndpoint }
							onChange={ ( val ) => {
								updateField( 'jobsEndpoint', val );
							} }
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label="Client ID"
							value={ settings.clientId }
							onChange={ ( val ) => {
								updateField( 'clientId', val );
							} }
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label="Client Secret"
							value={ settings.clientSecret }
							onChange={ ( val ) => {
								updateField( 'clientSecret', val );
							} }
						/>
					</div>
				</PanelBody>
				<PanelBody title="Sync Settings">
					<RadioControl
						help="Configure how often the site should sync with the ORC database."
						label="Interval"
						selected={ settings.syncInterval }
						options={ [
							{ label: 'Hourly', value: 'hourly' },
							{ label: 'Twice Daily', value: 'twicedaily' },
							{ label: 'Daily', value: 'daily' },
						] }
						onChange={ ( val ) => {
							updateField( 'syncInterval', val );
						} }
					/>
					<TimePicker.TimeInput
						is12Hour={ true }
						label="Time for sync to run"
						onChange={ ( val ) => {
							console.log( 'Selected time:', val );
							updateField( 'syncTime', val );
						} }
						value={ settings.syncTime }
					/>
				</PanelBody>
			</Panel>
			<Button
				style={ { alignSelf: 'flex-end' } }
				variant="primary"
				type="submit"
				disabled={ isSaving || isLoading }
				onClick={ () => {} }
			>
				Save Settings
			</Button>
			<div style={ { position: 'relative' } }>
				<SnackbarList
					style={ { top: '100%' } }
					notices={ notices }
					onRemove={ ( id ) => {
						setNotices( ( prev ) =>
							prev.filter( ( notice ) => notice.id !== id )
						);
					} }
				/>
			</div>
		</form>
	);
}
