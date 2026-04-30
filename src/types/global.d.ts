declare module '*.css';
declare module '*.scss';
type TimeInputValue = {
	hours: number;
	minutes: number;
};
/** Shape of the full settings object returned by the REST API */
type Settings = {
	jobsEndpoint: string;
	clientId: string;
	clientSecret: string;
	syncInterval: 'hourly' | 'daily' | 'twicedaily';
	syncTime: TimeInputValue;
};
