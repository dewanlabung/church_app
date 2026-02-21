const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': csrfToken,
};

export async function api(url, options = {}) {
    const config = {
        headers: { ...headers },
        ...options,
    };

    if (options.body instanceof FormData) {
        delete config.headers['Content-Type'];
        config.body = options.body;
    } else if (options.body && typeof options.body === 'object') {
        config.body = JSON.stringify(options.body);
    }

    const response = await fetch(url, config);

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Request failed' }));
        throw new Error(error.message || 'Request failed');
    }

    return response.json();
}

export const get = (url) => api(url);
export const post = (url, body) => api(url, { method: 'POST', body });
export const put = (url, body) => api(url, { method: 'PUT', body });
export const del = (url) => api(url, { method: 'DELETE' });
export const upload = (url, formData, method = 'POST') => api(url, { method, body: formData });
