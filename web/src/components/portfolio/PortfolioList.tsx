"use client";

import type { PortfolioItem } from "@/lib/types/portfolio";

interface PortfolioListProps {
  items: PortfolioItem[];
  onEdit: (item: PortfolioItem) => void;
  onAdd: () => void;
}

export default function PortfolioList({ items, onEdit, onAdd }: PortfolioListProps) {
  if (items.length === 0) {
    return (
      <div className="text-center py-12 text-gray-500">
        <p>Aucune réalisation dans votre portfolio.</p>
        <button
          onClick={onAdd}
          className="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Ajouter votre première réalisation
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-lg font-semibold">Portfolio ({items.length})</h2>
        <button
          onClick={onAdd}
          className="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700"
        >
          + Ajouter
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {items.map((item) => (
          <button
            key={item.id}
            onClick={() => onEdit(item)}
            className="text-left border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <div className="aspect-video bg-gray-100">
              {item.media.length > 0 ? (
                <img
                  src={
                    item.media.find((m) => m.type === "image")?.url_processed ||
                    item.media.find((m) => m.type === "image")?.url ||
                    ""
                  }
                  alt={item.title}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                  Aucune photo
                </div>
              )}
            </div>
            <div className="p-3">
              <h3 className="font-medium truncate">{item.title}</h3>
              <div className="text-sm text-gray-500 mt-1">
                {item.location && <span>{item.location}</span>}
                {item.event_date && (
                  <span>
                    {item.location ? " · " : ""}
                    {new Date(item.event_date).toLocaleDateString("fr-FR")}
                  </span>
                )}
              </div>
              <div className="text-xs text-gray-400 mt-1">
                {item.media.length} média{item.media.length > 1 ? "s" : ""}
              </div>
            </div>
          </button>
        ))}
      </div>
    </div>
  );
}
