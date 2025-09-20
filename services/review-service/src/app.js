require('dotenv').config();
const express = require('express');
const cors = require('cors');
const reviewRoutes = require('./routes');

const app = express();
const port = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use('/api', reviewRoutes);

app.listen(port, () => {
  console.log(`Review service listening on port ${port}`);
});