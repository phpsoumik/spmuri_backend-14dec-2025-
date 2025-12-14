// API Configuration
const API_CONFIG = {
    development: {
        baseUrl: 'http://localhost:8000',
        timeout: 30000
    },
    production: {
        baseUrl: 'https://yourdomain.com',
        timeout: 30000
    }
};

// Auto detect environment
const getApiConfig = () => {
    const hostname = window.location.hostname;
    
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        return API_CONFIG.development;
    } else {
        return API_CONFIG.production;
    }
};

// Export for use
window.API_BASE_URL = getApiConfig().baseUrl;