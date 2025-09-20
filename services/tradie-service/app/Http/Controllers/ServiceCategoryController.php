<?php
namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        return response()->json(ServiceCategory::all());
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:service_categories',
            'description' => 'nullable|string'
        ]);
        $category = ServiceCategory::create($request->only(['name', 'description']));
        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = ServiceCategory::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);
        $this->validate($request, [
            'name' => 'sometimes|required|unique:service_categories,name,' . $id,
            'description' => 'nullable|string'
        ]);
        $category->update($request->only(['name', 'description']));
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = ServiceCategory::findOrFail($id);
        $category->delete();
        return response()->json(['deleted' => true]);
    }
}
