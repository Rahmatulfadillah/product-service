const express = require('express');

const cors = require('cors');

const productRouter = require('./routes/productRoutes');

const app = express();

app.use(cors());

app.use(express.json());

app.get('/health', (req, res) => {
    res.json({
        status: "ok",
        service: "product-service"
    });
});

app.use("/products", productRouter);

app.use((req, res) => {
    res.status(404).json({
        message: "endpoint tidak dikenal"
    });
});

module.exports = app;