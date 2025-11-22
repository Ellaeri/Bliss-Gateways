// Run: node server.js
const express = require("express");
const cors = require("cors");
const bodyParser = require("body-parser");
const Amadeus = require("amadeus");

const app = express();
app.use(cors());
app.use(bodyParser.json());

// Amadeus API setup
const amadeus = new Amadeus({
  clientId: "gGF4RQHiDr8tnwxFASMANA6ACznYRqJI",
  clientSecret: "o20Xy5SzbDKwndAs"
});

// Search Flights 
app.get("/search-flights", async (req, res) => {
  try {
    const { origin, destination, departureDate, returnDate, adults, currencyCode } = req.query;

    if (!origin || !destination || !departureDate) {
      return res.status(400).json({ success: false, message: "Missing parameters (origin,destination,departureDate)" });
    }

    const params = {
      originLocationCode: origin,
      destinationLocationCode: destination,
      departureDate,
      adults: adults ? parseInt(adults, 10) : 1,
      max: 50 
    };

    if (returnDate) {
      params.returnDate = returnDate;
    }
    if (currencyCode) {
      params.currencyCode = currencyCode;
    }

    const response = await amadeus.shopping.flightOffersSearch.get(params);

    // Return the Amadeus response.data to the frontend
    res.json({ success: true, data: response.data || [] });
  } catch (error) {
    console.error("❌ Amadeus search error:", (error && (error.output || error.description)) || error);
    res.status(500).json({ success: false, message: "Flight search failed." });
  }
});

// Airport / City Search
app.get("/search-airports", async (req, res) => {
  try {
    const { keyword, subType } = req.query;
    if (!keyword) return res.status(400).json({ error: "Keyword is required" });

    const response = await amadeus.referenceData.locations.get({
      keyword,
      subType: subType || "AIRPORT"
    });

    res.json(response.data || []);
  } catch (err) {
    console.error("❌ Airport search error:", (err && (err.output || err.description)) || err);
    res.status(500).json({ error: "Failed to fetch airports" });
  }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => console.log(`✅ Server running at http://localhost:${PORT}`));
