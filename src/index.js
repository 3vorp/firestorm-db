const Collection = require("./collection.js");
const FirestormFiles = require("./FirestormFiles.js");

/**
 * @typedef FirestormCreationOption
 * @property {string} [name] - Instance name (can be helpful for debugging)
 * @property {string} [address] - Firestorm server address
 * @property {token} [token] - Firestorm write token
 */

/**
 * A unique Firestorm instance, representing a single Firestorm server and its collections
 */
class Firestorm {
	/** @ignore */
	_name;

	/** @ignore */
	_address;

	/** @ignore */
	_token;

	/** Firestorm file manager */
	files = new FirestormFiles();

	/**
	 * Create a new Firestorm instance
	 * - All parameters are optional and can be edited using the name, address, and token fields
	 * @param {FirestormCreationOption} [parameters] - Firestorm instance name, server address, and write token
	 */
	constructor({ name, address, token } = {}) {
		this.name = name;
		this.address = address;
		this.token = token;
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

	get name() {
		return this._name || this.address;
	}

	set name(newValue) {
		this.name = String(newValue);
	}

	get token() {
		return this._token;
	}

	set token(newValue) {
		this._token = newValue;
	}

	get address() {
		return this._address;
	}

	set address(newValue) {
		if (!newValue.endsWith("/")) newValue += "/";
		this._address = newValue;
	}
}

/**
 * @namespace firestorm
 */
module.exports = {
	/**
	 * Create a new Firestorm instance
	 * - All parameters are optional and can be edited using the name, address, and token fields
	 * @param {FirestormCreationOption} [parameters] - Firestorm instance name, server address, and write token
	 * @returns {Firestorm} Firestorm instance
	 */
	createFirestorm(params) {
		return new Firestorm(params);
	},
	clientVersion: require("../package.json").version,
};
