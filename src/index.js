const axios = require("axios").default;

const Collection = require("./collection.js");
const FirestormFiles = require("./files.js");

const { __extract_data } = require("./utils.js");

/**
 * @typedef FirestormCreationOption
 * @property {string} [name] - Instance name (can be helpful for debugging)
 * @property {string} [address] - Firestorm server address
 * @property {token} [token] - Firestorm write token
 */

/**
 * Represents a Firestorm-powered server and its collections, tokens, and setup
 */
class Firestorm {
	/** @ignore */
	_name;
	/** @ignore */
	_address;
	/** @ignore */
	_token;

	/**
	 * Firestorm file manager
	 * @type {FirestormFiles}
	 */
	files;

	/**
	 * Create a new Firestorm instance
	 * - All parameters are optional and can be edited using the name, address, and token fields
	 * @param {FirestormCreationOption} [params] - Firestorm instance name, server address, and write token
	 */
	constructor({ name, address, token } = {}) {
		this.name = name;
		this.address = address;
		this.token = token;
		this.files = new FirestormFiles(this);
	}

	/**
	 * Create a new Firestorm collection instance
	 * @template T
	 * @param {string} name - The name of the collection
	 * @param {Function} [addMethods] - Additional methods and data to add to the objects
	 * @returns {Collection<T>} The collection instance
	 */
	collection(name, addMethods = (el) => el) {
		return new Collection(this, name, addMethods);
	}

	// jsdoc has a really hard time dealing with getters/setters so this makes it look decent

	/** @type {string} */
	get name() {
		return this._name || this.address;
	}

	/** @ignore */
	set name(newValue) {
		this._name = String(newValue);
	}

	/** @type {string} */
	get token() {
		return this._token;
	}

	/** @ignore */
	set token(newValue) {
		this._token = newValue;
	}

	/** @type {string} */
	get address() {
		return this._address;
	}

	/** @ignore */
	set address(newValue) {
		if (newValue && !newValue.endsWith("/")) newValue += "/";
		this._address = newValue;
	}

	/**
	 * Get the current version of Firestorm
	 * @type {string}
	 */
	get clientVersion() {
		return require("../package.json").version;
	}

	/**
	 * Get the version of Firestorm used on the provided server
	 * @type {Promise<string>}
	 */
	get serverVersion() {
		if (!this.address)
			throw new Error(`Address for Firestorm instance "${this.instance.name}" was not configured`);

		return __extract_data(
			axios.get(`${this.address}version.php`, {
				data: {
					token: this.token,
				},
			}),
		);
	}

	/**
	 * Check whether the server-side Firestorm version is compatible with the client
	 * @returns {Promise<boolean>} - Whether the versions match
	 */
	async isCompatibleAddress() {
		const serverVersion = await this.serverVersion;
		const splitServer = serverVersion.split(".");
		const splitClient = this.clientVersion.split(".");

		// patch version keeps server compatibility
		return splitServer[0] === splitClient[0] && splitServer[1] === splitClient[1];
	}
}

/**
 * Create a new Firestorm instance
 * - All parameters are optional and can be edited using the name, address, and token fields
 * @param {FirestormCreationOption} [params] - Firestorm instance name, server address, and write token
 * @returns {Firestorm} Firestorm instance
 */
exports.createFirestorm = (params = {}) => new Firestorm(params);
