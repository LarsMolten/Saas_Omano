import { NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function GET() {
  try {
    const res = await fetch(`${API_URL}/v1/homepage`, {
      headers: { Accept: "application/json" },
      next: { revalidate: 3600 },
    });
    const data = await res.json();
    return NextResponse.json(data, { status: res.status });
  } catch {
    return NextResponse.json(
      { featured: [], categories: [], recent: [] },
      { status: 200 }
    );
  }
}
