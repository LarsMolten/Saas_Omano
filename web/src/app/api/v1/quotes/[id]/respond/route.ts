import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;
  const body = await request.json();
  const cookie = request.headers.get("cookie") ?? "";
  const res = await fetch(`${API_URL}/v1/quotes/${id}/respond`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", Accept: "application/json", Cookie: cookie },
    body: JSON.stringify(body),
  });
  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
