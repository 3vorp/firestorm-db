import type { FirestormFiles } from "./files.d.ts";
import type { Collection, AddMethods } from "./collection.d.ts";

export interface FirestormCreationOption {
	/** Instance name (can be helpful for debugging) */
	name?: string;
	/** Firestorm server address */
	address?: string;
	/** Firestorm write token */
	token?: string;
}

export interface Firestorm {
	/** Name of the Firestorm instance (defaults to address) */
	name: string;

	/** Address of the Firestorm instance */
	address?: string;

	/** Writing token for the Firestorm instance */
	token?: string;

	/** Firestorm file manager */
	readonly files: FirestormFiles;

	/**
	 * Create a new Firestorm collection instance
	 * @param name - The name of the collection
	 * @param addMethods - Additional methods and data to add to the objects
	 * @returns The collection instance
	 */
	collection<T>(name: string, addMethods?: AddMethods<T>): Collection<T>;
}

/**
 * Create a new Firestorm instance
 * - All parameters are optional and can be edited using the name, address, and token fields
 * @param params - Firestorm instance name, server address, and write token
 * @returns {Firestorm} Firestorm instance
 */
export function createFirestorm(params?: FirestormCreationOption): Firestorm;

/** Get the current version of Firestorm */
export const clientVersion: string;

export type * from "./collection.d.ts";
export type * from "./files.d.ts";
export type * from "./utils.d.ts";
