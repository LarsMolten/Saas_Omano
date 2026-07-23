"use client";

import { useState, useRef } from "react";
import type { PortfolioItem } from "@/lib/types/portfolio";

interface PortfolioFormProps {
  providerId: number;
  item?: PortfolioItem | null;
  onSave: (item: PortfolioItem) => void;
  onDelete?: () => void;
  onCancel: () => void;
}

export default function PortfolioForm({
  providerId,
  item,
  onSave,
  onDelete,
  onCancel,
}: PortfolioFormProps) {
  const [title, setTitle] = useState(item?.title ?? "");
  const [description, setDescription] = useState(item?.description ?? "");
  const [eventDate, setEventDate] = useState(item?.event_date?.split("T")[0] ?? "");
  const [location, setLocation] = useState(item?.location ?? "");
  const [budgetApprox, setBudgetApprox] = useState(item?.budget_approx ?? "");
  const [files, setFiles] = useState<File[]>([]);
  const [previews, setPreviews] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const fileInputRef = useRef<HTMLInputElement>(null);

  const isEdit = !!item;

  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const selected = Array.from(e.target.files ?? []);
    const limited = selected.slice(0, 10 - files.length);
    setFiles((prev) => [...prev, ...limited]);

    limited.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (ev) => {
        setPreviews((prev) => [...prev, ev.target?.result as string]);
      };
      reader.readAsDataURL(file);
    });
  }

  function removeFile(index: number) {
    setFiles((prev) => prev.filter((_, i) => i !== index));
    setPreviews((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError("");

    const formData = new FormData();
    formData.append("title", title);
    if (description) formData.append("description", description);
    if (eventDate) formData.append("event_date", eventDate);
    if (location) formData.append("location", location);
    if (budgetApprox) formData.append("budget_approx", budgetApprox);

    files.forEach((file) => {
      formData.append("media[]", file);
    });

    try {
      const url = isEdit
        ? `/api/v1/portfolio/${item.id}`
        : `/api/v1/providers/${providerId}/portfolio`;

      const method = isEdit ? "POST" : "POST";

      const res = await fetch(url, {
        method,
        body: formData,
      });

      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.message || "Erreur lors de la sauvegarde");
      }

      const saved = await res.json();
      onSave(saved);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur inconnue");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block text-sm font-medium mb-1">Titre *</label>
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          required
          className="w-full border rounded px-3 py-2"
          placeholder="Ex: Mariage au Palais des Fêtes"
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Description</label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          rows={3}
          className="w-full border rounded px-3 py-2"
          placeholder="Décrivez le projet, l'événement..."
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">Date événement</label>
          <input
            type="date"
            value={eventDate}
            onChange={(e) => setEventDate(e.target.value)}
            className="w-full border rounded px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Lieu</label>
          <input
            type="text"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            className="w-full border rounded px-3 py-2"
            placeholder="Ville ou lieu"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Budget approx. (OMR)</label>
          <input
            type="number"
            step="0.01"
            min="0"
            value={budgetApprox}
            onChange={(e) => setBudgetApprox(e.target.value)}
            className="w-full border rounded px-3 py-2"
            placeholder="0.00"
          />
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Photos / Vidéos</label>
        <div className="flex flex-wrap gap-2 mb-2">
          {previews.map((src, i) => (
            <div key={i} className="relative w-20 h-20">
              <img src={src} alt="" className="w-full h-full object-cover rounded" />
              <button
                type="button"
                onClick={() => removeFile(i)}
                className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center"
              >
                x
              </button>
            </div>
          ))}
        </div>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*,video/*"
          multiple
          onChange={handleFileChange}
          className="hidden"
        />
        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={files.length >= 10}
          className="border border-dashed rounded px-4 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
        >
          + Ajouter des fichiers (max 10)
        </button>
      </div>

      {error && <p className="text-red-500 text-sm">{error}</p>}

      <div className="flex gap-2">
        <button
          type="submit"
          disabled={saving}
          className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
        >
          {saving ? "Enregistrement..." : isEdit ? "Modifier" : "Créer"}
        </button>
        <button
          type="button"
          onClick={onCancel}
          className="border px-4 py-2 rounded hover:bg-gray-50"
        >
          Annuler
        </button>
        {isEdit && onDelete && (
          <button
            type="button"
            onClick={onDelete}
            className="ml-auto bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
          >
            Supprimer
          </button>
        )}
      </div>
    </form>
  );
}
