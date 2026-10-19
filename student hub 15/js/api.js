// This file only loads and validates JSON data.
export async function parseJson(response) {
  const data = await response.json();

  if (!Array.isArray(data)) {
    throw new Error("JSON data must be an array.");
  }

  return data;
}

export async function fetchJson(fileName) {
  try {
    const response = await fetch(`data/${fileName}.json`);

    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`);
    }

    const data = await parseJson(response);

    console.log(`${fileName}.json loaded successfully.`);
    console.log(`Number of ${fileName} records loaded:`, data.length);
    return data;
  } catch (error) {
    console.error(`Could not load ${fileName}.json:`, error);
    throw error;
  }
}
