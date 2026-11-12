<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:60', 'unique:categories,name']],
            ['name.required' => 'Nama kategori wajib diisi.', 'name.unique' => 'Kategori ini sudah ada.']
        );

        Category::create(['name' => trim($data['name'])]);

        return back()->with('success', "Kategori \"{$data['name']}\" ditambahkan.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete(); // produknya tetap ada, hanya menjadi tanpa kategori

        return back()->with('success', "Kategori \"{$name}\" dihapus. Produknya menjadi tanpa kategori.");
    }
}
