//temporary such that tests can be done on postman

const express = require('express');
const cors = require('cors');
const app = express();
const axios = require('axios');
const port = 3000;

app.use(cors()); // can be used to limit where receiving and sending data
app.use(express.json());

app.get('/', (req,res) => {
    res.send("Hello from Node.js server!");
});

app.post('/data', (req,res) => {
    const received = req.body;
    console.log("Rec from client: ", received);
    res.json({status: 'success', received});
});

app.listen(port, () => {
    console.log(`server running at https://localhost:${port}`);
});

app.post('/api', async (req, res) => {
    try{
        const apires = await axios.post("http://localhost:8000/api.php", req.body);
        res.json({ fromAPI: apires.data });
    }
    catch(err) {res.status(500).send("Failure to contact api");}
});


