//temporary such that tests can be done on postman

const express = require('express');
const cors = require('cors');
const app = express();
const axios = require('axios');
const port = 3000;

app.use(cors()); // can be used to limit where receiving and sending data
app.use(express.json());



app.post('/api', async (req, res) => {
    try{
        const apires = await axios.post("http://localhost:8000/api.php", req.body);
        res.json({ fromAPI: apires.data });
    }
    catch (err) {
    console.error("AXIOS ERROR:", err.message);
    if (err.response) {
        console.error("Response error:", err.response.status, err.response.data);
    } else if (err.request) {
        console.error("No response received:", err.request);
    } else {
        console.error("Unknown error:", err);
    }
    res.status(500).send("Failure to contact api");
}
});


app.listen(port, () => {
    console.log(`server running at https://localhost:${port}`);
});


