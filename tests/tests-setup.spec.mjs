// @ts-check

import { expect } from "chai";

import { firestorm, ADDRESS, TOKEN } from "./tests.env.mjs";
import { createFirestorm } from "../src/index.js";

describe("Wrapper information", () => {
	it("binds usable address", () => {
		firestorm.address = ADDRESS;

		const actual = firestorm.address;
		expect(actual).to.equal(ADDRESS, "Incorrect address bind");
	});

	it("can use constructor address", () => {
		firestorm.address = ADDRESS;
		const tmp = createFirestorm({ address: ADDRESS });
		expect(firestorm.address).to.equal(tmp.address, "Address was not set correctly");
	});

	it("binds usable token", () => {
		firestorm.token = TOKEN;

		const actual = firestorm.token;
		expect(actual).to.equal(TOKEN, "Incorrect token bind");
	});

	it("can use constructor token", () => {
		firestorm.token = TOKEN;
		const tmp = createFirestorm({ token: TOKEN });
		expect(firestorm.token).to.equal(tmp.token, "Token was not set correctly");
	});

	it("gets the version field correctly", async () => {
		firestorm.token = TOKEN;

		const version = await firestorm.serverVersion;
		expect(version).to.match(/\d+\.\d+\.\d+/, "Version did not match expected schema");
	});

	it("server and client versions match", async () => {
		expect(await firestorm.isCompatibleAddress()).to.be.true;
	});
});
