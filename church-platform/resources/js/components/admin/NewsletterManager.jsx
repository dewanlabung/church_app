import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, extractPaginatedData } from '../shared/api';

export default function NewsletterManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [searchTimeout, setSearchTimeout] = useState(null);

    const fetchItems = async (page = 1, query = search) => {
        setLoading(true);
        try {
            let url = `/api/newsletter/subscribers?page=${page}`;
            if (query) {
                url += `&search=${encodeURIComponent(query)}`;
            }
            const data = await get(url);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        if (searchTimeout) clearTimeout(searchTimeout);
        const timeout = setTimeout(() => {
            fetchItems(1, value);
        }, 400);
        setSearchTimeout(timeout);
    };

    const columns = [
        { key: 'email', label: 'Email' },
        { key: 'name', label: 'Name', render: (r) => r.name || '-' },
        {
            key: 'is_active', label: 'Status',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {r.is_active ? 'Active' : 'Unsubscribed'}
                </span>
            ),
        },
        {
            key: 'subscribed_at', label: 'Subscribed',
            render: (r) => r.subscribed_at ? new Date(r.subscribed_at).toLocaleDateString() : (r.created_at ? new Date(r.created_at).toLocaleDateString() : '-'),
        },
        {
            key: 'unsubscribed_at', label: 'Unsubscribed',
            render: (r) => r.unsubscribed_at ? new Date(r.unsubscribed_at).toLocaleDateString() : '-',
        },
    ];

    const activeCount = items.filter(i => i.is_active).length;
    const totalSubscribers = meta?.total || items.length;

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Newsletter Subscribers</h2>
                    <p className="text-sm text-gray-500 mt-1">Subscribers who signed up via the public newsletter form.</p>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div className="bg-white rounded-xl shadow-sm border p-4">
                    <div className="text-sm font-medium text-gray-500">Total Subscribers</div>
                    <div className="text-2xl font-bold text-gray-900 mt-1">{totalSubscribers}</div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border p-4">
                    <div className="text-sm font-medium text-gray-500">Active (this page)</div>
                    <div className="text-2xl font-bold text-green-600 mt-1">{activeCount}</div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border p-4">
                    <div className="text-sm font-medium text-gray-500">Unsubscribed (this page)</div>
                    <div className="text-2xl font-bold text-red-600 mt-1">{items.length - activeCount}</div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="bg-white rounded-xl shadow-sm border">
                <div className="p-4 border-b">
                    <div className="relative">
                        <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            value={search}
                            onChange={handleSearch}
                            placeholder="Search by name or email..."
                            className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                        />
                    </div>
                </div>
                {loading ? (
                    <div className="text-center py-12 text-gray-500">Loading subscribers...</div>
                ) : (
                    <DataTable columns={columns} data={items} />
                )}
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
        </div>
    );
}
