import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function GET(request: NextRequest) {
  const cookie = request.headers.get("cookie") ?? "";

  const res = await fetch(`${API_URL}/v1/admin/categories`, {
    headers: { Cookie: cookie, Accept: "application/json" },
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}

export async function POST(request: NextRequest) {
  const cookie = request.headers.get("cookie") ?? "";
  const body = await request.json();

  const res = await fetch(`${API_URL}/v1/admin/categories`, {
    method: "POST",
    headers: { Cookie: cookie, Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
