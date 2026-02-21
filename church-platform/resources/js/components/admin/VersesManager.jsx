import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function VersesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ reference: '', verse_text: '', display_date: '', translation: '' });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/verses?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    const openCreate = () => { setEditing(null); setForm({ reference: '', verse_text: '', display_date: '', translation: '' }); setShowModal(true); };
    const openEdit = (item) => { setEditing(item); setForm({ reference: item.reference, verse_text: item.verse_text, display_date: item.display_date, translation: item.translation || '' }); setShowModal(true); };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) { await put(`/api/verses/${editing.id}`, form); setAlert({ type: 'success', message: 'Verse updated.' }); }
            else { await post('/api/verses', form); setAlert({ type: 'success', message: 'Verse created.' }); }
            setShowModal(false); fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this verse?')) return;
        try { await del(`/api/verses/${item.id}`); setAlert({ type: 'success', message: 'Verse deleted.' }); fetchItems(); }
        catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const columns = [
        { key: 'reference', label: 'Reference' },
        { key: 'verse_text', label: 'Text', render: (r) => (r.verse_text || '').substring(0, 60) + '...' },
        { key: 'display_date', label: 'Date' },
        { key: 'translation', label: 'Translation' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Verses of the Day</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Verse</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Verse' : 'Add Verse'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Reference" name="reference" value={form.reference} onChange={handleChange} required placeholder="John 3:16" />
                    <FormField label="Text" name="verse_text" type="textarea" value={form.verse_text} onChange={handleChange} required />
                    <FormField label="Date" name="display_date" type="date" value={form.display_date} onChange={handleChange} required />
                    <FormField label="Translation" name="translation" value={form.translation} onChange={handleChange} placeholder="NIV, KJV, etc." />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
