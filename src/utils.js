exports.__extract_data = async (request) => {
	// does nothing if request is synchronous
	const res = await request;
	if ("data" in res) return res.data;
	return res;
};
