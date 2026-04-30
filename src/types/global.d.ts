declare module '*.css';
declare module '*.scss';

/** Shape of the full settings object returned by the REST API */
type Settings = {
	jobsEndpoint: string;
	clientId: string;
	clientSecret: string;
	syncInterval: 'hourly' | 'daily' | 'twicedaily';
};
