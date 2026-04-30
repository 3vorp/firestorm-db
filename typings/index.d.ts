import type { FirestormFiles } from "./files.d.ts";
import type { Collection, AddMethods } from "./collection.d.ts";

export interface FirestormCreationOption {
	name?: string;
	address?: string;
	token?: string;
}

declare class Firestorm {
	/** Name of the Firestorm instance (defaults to address) */
	public name: string;

	/** Address of the Firestorm instance */
	public address?: string;

	/** Writing token for the Firestorm instance */
	public token?: string;

	/** Firestorm file manager */
	public readonly files: FirestormFiles;

	/**
	 * Create a new Firestorm collection instance
	 * @param name - The name of the collection
	 * @param addMethods - Additional methods and data to add to the objects
	 * @returns The collection instance
	 */
	public collection<T>(name: string, addMethods?: AddMethods<T>): Collection<T>;
}

export function createFirestorm(params?: FirestormCreationOption): Firestorm;
export const clientVersion: string;
export type * from "./collection.d.ts";
export type * from "./files.d.ts";
