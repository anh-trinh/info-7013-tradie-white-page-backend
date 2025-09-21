<?php
namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getAll()
    {
        return response()->json(ServiceCategory::orderBy('name')->get());
    }

    public function create(Request $request)
    {
        $this->validate($request, ['name' => 'required|string|unique:service_categories']);
        $category = ServiceCategory::create($request->all());
        return response()->json($category, 201);
    }

    public function update($id, Request $request)
    {
        $category = ServiceCategory::findOrFail($id);
        $category->fill($request->only(['name','description']));
        $category->save();
        return response()->json($category);
    }

    public function delete($id)
    {
        $category = ServiceCategory::findOrFail($id);
        $category->delete();
        return response()->json(['deleted' => true]);
    }
}
